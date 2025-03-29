<?php

namespace App\Http\Controllers;

use App\Events\CoursePurchased;
use App\Events\PixelEvent;
use App\Http\Helpers\Responses;
use App\Http\Requests\CelCashGateway\Webhooks\UpdatePaymentRequest;
use App\Http\Requests\CelCashGateway\Webhooks\VerifyDocumentsRequest;
use App\Mail\AccountRepproved;
use App\Mail\Sales\BuyerMail;
use App\Models\CelcashConfirmHashWebhook;
use App\Models\CelcashPayments;
use App\Models\PendingPixelEvents;
use App\Models\User;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCpfCredentials;
use App\Models\ZendryTokens;
use App\Services\PixelEventService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CelcashWebhooksController extends Controller
{
    public function documents(VerifyDocumentsRequest $request)
    {
        $validatedData = $request->validated();

        if ($validatedData['event'] != 'company.verifyDocuments') {
            try {
                $createLog = \App\Models\CelcashWebhook::create([
                    'webhook_title' => 'EVENTO NÃO AUTORIZADO',
                    'webhook_id' => $validatedData['webhookId'],
                    'webhook_event' => $validatedData['event'],
                    'webhook_data' => $request,
                ]);
            } catch (\Throwable $th) {
                Log::error('Ocorreu um erro ao salvar um log de crash do webhook!', ['error' => $th->getMessage()]);
            }

            return Responses::ERROR('Evento não autorizado!', $validatedData['event'], 1100, 400);
        }

        $verifyConfirmHash = CelcashConfirmHashWebhook::where('webhook_event', $validatedData['event'])
            ->where('confirm_hash', $validatedData['confirmHash'])
            ->exists();

        if (!$verifyConfirmHash) {
            return Responses::ERROR('Credenciais de confirmação incorretas!', null, 1200, 400);
        }

        $webhookUserEmail = $validatedData['Company']['emailContact'];

        $getUser = User::where('email', $webhookUserEmail)->first();

        if (!$getUser) {
            return Responses::ERROR('Usuário informado não localizado!', $webhookUserEmail, 1300, 400);
        }

        if ($getUser->account_type->value == 'PF') {
            $getDocuments = UserCelcashCpfCredentials::where('user_id', $getUser->id)
                ->with('documents')
                ->first();
        }

        if ($getUser->account_type->value == 'PJ') {
            $getDocuments = UserCelcashCnpjCredentials::where('user_id', $getUser->id)
                ->with('documents')
                ->first();
        }

        if (!$getDocuments) {
            return Responses::ERROR('O usuário não possui documentos cadastrados!', null, 1400, 400);
        }

        $getVerificationStatus = $validatedData['Company']['Verification']['status'];
        $getVerificationReasons = null;

        if ($getVerificationStatus == 'denied')
            $getVerificationReasons = $validatedData['Company']['Verification']['Reasons'];

        $getDocuments->documents->update([
            'document_status' => $getVerificationStatus,
            'document_refused_reason' => $getVerificationReasons,
        ]);

        return Responses::SUCCESS('Status do documento atualizado com sucesso!', null, 200);
    }

    public function transactions(UpdatePaymentRequest $request)
    {
        $validatedData = $request->validated();

        $getTransaction = CelcashPayments::where('galax_pay_id', $validatedData['orderId'])
            ->with('payment_offers', function($query) {
                $query->where('type', 'principal')
                    ->with('offer', function($query) {
                        $query->with('product:id,email_support')
                            ->select(['id', 'product_id']);
                    })
                    ->select(['id', 'celcash_payments_id', 'products_offerings_id', 'type']);
            })
            ->first();

        if (!$getTransaction) {
            return Responses::ERROR('Transação não localizada!', null, 1300, 400);
        }

        $isPayedStatus = false;

        if ($getTransaction->type == 'pix') {
            if (
                $validatedData['status'] == 'paid' ||
                $validatedData['status'] == 'confirmed' ||
                $validatedData['status'] == 'received' ||
                $validatedData['status'] == 'received_in_cash' ||
                $validatedData['status'] == 'captured'
            ) {
                $status = 'payed_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['status'] == 'pending' ||
                $validatedData['status'] == 'awaiting_payment' ||
                $validatedData['status'] == 'waiting_payment'
            ) {
                $status = 'pending_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['status'] == 'refund_requested' ||
                $validatedData['status'] == 'refund_in_progress' ||
                $validatedData['status'] == 'refunded'
            ) {
                $status = 'refunded';
                $isPayedStatus = true;
            }

            if (
                $validatedData['status'] == 'infraction' ||
                $validatedData['status'] == 'chargeback_requested' ||
                $validatedData['status'] == 'chargeback' ||
                $validatedData['status'] == 'chargeback_dispute' ||
                $validatedData['status'] == 'prechargeback' ||
                $validatedData['status'] == 'awaiting_chargeback_reversal:' ||
                $validatedData['status'] == 'dunning_requested' ||
                $validatedData['status'] == 'dunning_received' ||
                $validatedData['status'] == 'awaiting_risk_analysis'
            ) {
                $status = 'chargeback';
                $isPayedStatus = true;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                try {
                    $buyerUser = User::create([
                        'name' => $getTransaction->buyer_name,
                        'email' => $getTransaction->buyer_email,
                        'password' => $generateRandomPassword
                    ]);
                }
                catch (\Exception $e) {
                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['webhookId'],
                        'webhook_event' => $validatedData['event'],
                        'webhook_data' => $request,
                    ]);
                }
            }

            $getTransaction->update([
                'status' => $status,
                'buyer_user_id' => $buyerUser->id
            ]);

            try {
                Mail::to($buyerUser->email)->send(new BuyerMail($buyerUser->name, $buyerUser->email, $getTransaction->payment_offers[0]->offer->product->email_support, $validatedData['orderId']));
            }
            catch (\Exception $e) {
                Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao tentar enviar um e-mail de pagamento |', [ 'ERRO' => $e->getMessage()]);
            }

            /*
             * =============================================================
             * DISPARA O EVENTO PARA RELACIONAR A COMPRA DA OFERTA AO USUARIO
             * E INICIA AS AULAS COM O PROGRESSO ZERADO
             * =============================================================
             */

            $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['message']['reference_code'])
                ->where('status', 'Waiting Payment')
                ->get();

            if ($pendingEvents->isNotEmpty()) {
                foreach ($pendingEvents as $event) {
                    try {
                        // Envia evento de conversão
                        event(new PixelEvent($event->offer_id, $event->event_name, $event->payload, $request->header('x-transaction-id')));
                        // Marca o evento como enviado
                        $event->update(['status' => 'sent']);
                    } catch (\Exception $e) {
                        // Se falhar, pode logar o erro e tentar novamente depois
                        $event->update(['status' => 'failed']);
                    }
                }
            }

            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }

    public function transactions_zendry(Request $request)
    {
        $validatedData = $request->validate([
            'message' => 'required',
            'message.reference_code' => 'required',
            'message.status' => 'required',
            'message.end_to_end' => 'required',
            'message.value_cents' => 'required',
            'md5' => 'required',
        ]);

        /*$getSecretToken = ZendryTokens::where('type', 'private_token')->first();

        $generateMd5String = "qrcode." . $validatedData['message']['reference_code'] . '.' . $validatedData['message']['end_to_end'] . '.' . $validatedData['message']['value_cents'] . '.' . trim($getSecretToken->value);
        $generateMd5 = md5($generateMd5String);

        if ($generateMd5 != $validatedData['md5']) {
            return Responses::ERROR('Ação não autorizada. Credenciais inválidas!', null, 1200, 400);
        }*/

        $getTransaction = CelcashPayments::where('galax_pay_id', $validatedData['message']['reference_code'])
            ->with('payment_offers', function($query) {
                $query->where('type', 'principal')
                    ->with('offer', function($query) {
                        $query->with('product:id,email_support')
                        ->select(['id', 'product_id']);
                    })
                ->select(['id', 'celcash_payments_id', 'products_offerings_id', 'type']);
            })
            ->first();

        if (!$getTransaction) {
            return Responses::ERROR('Transação não localizada!', null, 1300, 400);
        }

        $isPayedStatus = false;

        if ($getTransaction->type == 'pix') {
            if (
                $validatedData['message']['status'] == 'paid'
            ) {
                $status = 'payed_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['message']['status'] == 'awaiting_payment'
            ) {
                $status = 'pending_pix';
                $isPayedStatus = true;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                try {
                    $buyerUser = User::create([
                        'name' => $getTransaction->buyer_name,
                        'email' => $getTransaction->buyer_email,
                        'password' => $generateRandomPassword
                    ]);
                }
                catch (\Exception $e) {
                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['message']['reference_code'],
                        'webhook_event' => $validatedData['message'],
                        'webhook_data' => $request,
                    ]);
                }
            }

            $getTransaction->update([
                'status' => $status,
                'buyer_user_id' => $buyerUser->id
            ]);

            try {
                Mail::to($buyerUser->email)->send(new BuyerMail($buyerUser->name, $buyerUser->email, $getTransaction->payment_offers[0]->offer->product->email_support, $validatedData['message']['reference_code']));
            }
            catch (\Exception $e) {
                Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao tentar enviar um e-mail de pagamento |', [ 'ERRO' => $e->getMessage()]);
            }

            /*
             * =============================================================
             * DISPARA O EVENTO PARA RELACIONAR A COMPRA DA OFERTA AO USUARIO
             * E INICIA AS AULAS COM O PROGRESSO ZERADO
             * =============================================================
             */
            $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['orderId'])
                ->where('status', 'Waiting Payment')
                ->get();

            if($pendingEvents->isNotEmpty() ) {
                foreach ($pendingEvents as $event) {
                    try {
                        $pixel_data=PixelEventService::FormatDataPixel($event->payload);
                        Log::info("Colocando na fila o evento para disparar o pixel Via Confirmacao de Pagamento", ["pixel" => $pixel_data]);
                        // Envia evento de conversão
                        event(new PixelEvent($event->offer_id, $event->event_name, $pixel_data, $request->header('x-transaction-id')));
                        // Marca o evento como enviado
                        $event->update(['status' => 'sent']);
                    } catch (\Exception $e) {
                        // Se falhar, pode logar o erro e tentar novamente depois
                        $event->update(['status' => 'failed']);
                    }
                }
            }

            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }
}
