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
use App\Models\Configuration;
use App\Models\PendingPixelEvents;
use App\Models\User;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCpfCredentials;
use App\Models\ZendryTokens;
use App\Services\PixelEventService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
                $isPayedStatus = false;
            }

            if (
                $validatedData['status'] == 'refund_requested' ||
                $validatedData['status'] == 'refund_in_progress' ||
                $validatedData['status'] == 'refunded'
            ) {
                $status = 'refunded';
                $isPayedStatus = false;
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
                $isPayedStatus = false;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                $data = [
                    'name' => $getTransaction->buyer_name,
                    'email' => $getTransaction->buyer_email,
                    'password' => $generateRandomPassword
                ];

                $configsData = Configuration::first();

                $data['withdrawal_period'] = $configsData->default_withdraw_period ?? 0;
                $data['withdrawal_tax'] = $configsData->default_withdraw_tax ?? 1.5;
                $data['pix_tax_value'] = $configsData->default_pix_tax_value ?? 1.99;
                $data['pix_money_tax_value'] = $configsData->default_pix_money_tax_value ?? 1.3;
                $data['card_tax_value'] = $configsData->default_card_tax_value ?? 4.99;
                $data['card_money_tax_value'] = $configsData->default_card_money_tax_value ?? 1.5;
                $data['cash_in_adquirer_name'] = $configsData->default_cash_in_adquirer ?? 'zendry';
                $data['cash_out_adquirer_name'] = $configsData->default_cash_out_adquirer ?? 'zendry';

                Log::info("| CRIÇÃO DE CONTA - COMPRADOR ".'| Criando conta de usuário comprador', ['Dados da criação do usuário' => $data]);

                try {
                    $buyerUser = User::create($data);
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

            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        $pendingEvents = PendingPixelEvents::where('payment_id',$validatedData['orderId'])
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

            if($getTransaction->payment_offers[0]->offer->utmify_token) {
                try {
                    $body = [
                        "orderId" => $validatedData['orderId'],
                        "platform" => "BullsPay",
                        "paymentMethod" => "pix",
                        "status" => "paid",
                        "createdAt" => $getTransaction->created_at,
                        "approvedDate" => $getTransaction->updated_at,
                        "refundedAt" => null,
                        "customer" => [
                            "name" => $getTransaction->buyer_name,
                            "email" => $getTransaction->buyer_email,
                            "phone" => null,
                            "document" => $getTransaction->buyer_document_cpf,
                            "country" => "BR",
                            "IP" =>null
                        ],
                        "products" =>[
                            [
                                "id" => $getTransaction->galax_pay_id,
                                "name" => $getTransaction->payment_offers[0]->offer->product->product_name,
                                "planId" => null,
                                "planName" => null,
                                "quantity" => 1,
                                "priceInCents" => $getTransaction->payment_offers[0]->offer->price * 100
                            ]
                        ],
                        "trackingParameters" => [
                            "src" => $getTransaction->src,
                            "sck"=>  $getTransaction->sck,
                            "utm_source"=>  $getTransaction->utm_source,
                            "utm_campaign"=> $getTransaction->utm_campaign,
                            "utm_medium"=> $getTransaction->utm_medium,
                            "utm_content"=> $getTransaction->utm_content,
                            "utm_term"=> $getTransaction->utm_term,
                        ],
                        "commission" => [
                            "totalPriceInCents" => $getTransaction->total_value,
                            "gatewayFeeInCents" => $getTransaction->value_to_platform,
                            "userCommissionInCents" => $getTransaction->value_to_receiver
                        ],
                        "isTest" => false
                    ];
                    $headers = [
                        'x-api-token' => $getTransaction->payment_offers[0]->offer->utmify_token
                    ];
                    $utmify =Http::WithHeaders($headers)
                        ->post(
                            'https://api.utmify.com.br/api-credentials/orders',
                            $body
                        );
                    Log::info("Evento enviado ao UTMIFY ", ["Response" => $utmify]);
                } catch (\Exception $e) {
                    Log::error("Erro ao enviar Request para UTMIFY", ["erro" => $e->getMessage()]);
                }
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
            'message.value_cents' => 'required'
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
                        $query->with('product', function($query) {
                            $query->with('user:id,email');
                            $query->select(['id', 'email_support', 'product_name', 'user_id']);
                        })
                        ->select(['id', 'product_id', 'utmify_token']);
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
                $isPayedStatus = false;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                $data = [
                    'name' => $getTransaction->buyer_name ?? "Bulls Pay",
                    'email' => $getTransaction->buyer_email ?? "compras@bullspay.com.br",
                    'password' => $generateRandomPassword
                ];

                $configsData = Configuration::first();

                $data['withdrawal_period'] = $configsData->default_withdraw_period ?? 0;
                $data['withdrawal_tax'] = $configsData->default_withdraw_tax ?? 1.5;
                $data['pix_tax_value'] = $configsData->default_pix_tax_value ?? 1.99;
                $data['pix_money_tax_value'] = $configsData->default_pix_money_tax_value ?? 1.3;
                $data['card_tax_value'] = $configsData->default_card_tax_value ?? 4.99;
                $data['card_money_tax_value'] = $configsData->default_card_money_tax_value ?? 1.5;
                $data['cash_in_adquirer_name'] = $configsData->default_cash_in_adquirer ?? 'zendry';
                $data['cash_out_adquirer_name'] = $configsData->default_cash_out_adquirer ?? 'zendry';

                Log::info("| CRIÇÃO DE CONTA - COMPRADOR ".'| Criando conta de usuário comprador', ['Dados da criação do usuário' => $data]);

                try {
                    $buyerUser = User::create($data);
                }
                catch (\Exception $e) {
                    Log::error('Erro ao criar o usuário pagante', ["erro" => $e->getMessage(), "data" => $data]);

                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['message']['reference_code'],
                        'webhook_event' => $validatedData['message'],
                        'webhook_data' => 'erro',
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

            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['message']['reference_code'])
                ->where('status', 'Waiting Payment')
                ->get();

            if($pendingEvents->isNotEmpty()  ) {
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
            if($getTransaction->payment_offers[0]->offer->utmify_token) {
                try {
                    $body = [
                        "orderId" => $validatedData['message']['reference_code'],
                        "platform" => "BullsPay",
                        "paymentMethod" => "pix",
                        "status" => "paid",
                        "createdAt" => $getTransaction->created_at,
                        "approvedDate" => $getTransaction->updated_at,
                        "refundedAt" => null,
                        "customer" => [
                            "name" => $getTransaction->buyer_name,
                            "email" => $getTransaction->buyer_email,
                            "phone" => null,
                            "document" => $getTransaction->buyer_document_cpf,
                            "country" => "BR",
                            "IP" =>null
                        ],
                        "products" =>[
                            [
                                "id" => $getTransaction->galax_pay_id,
                                "name" => $getTransaction->payment_offers[0]->offer->product->product_name,
                                "planId" => null,
                                "planName" => null,
                                "quantity" => 1,
                                "priceInCents" => $getTransaction->payment_offers[0]->offer->price * 100
                            ]
                        ],
                        "trackingParameters" => [
                            "src" => $getTransaction->src,
                            "sck"=>  $getTransaction->sck,
                            "utm_source"=>  $getTransaction->utm_source,
                            "utm_campaign"=> $getTransaction->utm_campaign,
                            "utm_medium"=> $getTransaction->utm_medium,
                            "utm_content"=> $getTransaction->utm_content,
                            "utm_term"=> $getTransaction->utm_term,
                        ],
                        "commission" => [
                            "totalPriceInCents" => $getTransaction->total_value,
                            "gatewayFeeInCents" => $getTransaction->value_to_platform,
                            "userCommissionInCents" => $getTransaction->value_to_receiver
                        ],
                        "isTest" => false
                    ];
                    $headers = [
                        'x-api-token' => $getTransaction->payment_offers[0]->offer->utmify_token
                    ];
                    $utmify =Http::WithHeaders($headers)
                        ->post(
                            'https://api.utmify.com.br/api-credentials/orders',
                            $body
                        );
                    Log::info("Evento enviado ao UTMIFY ", ["Response" => $utmify]);
                 } catch (\Exception $e) {
                    Log::error("Erro ao enviar Request para UTMIFY", ["erro" => $e->getMessage()]);
                }
            }

        try {
            $notificationResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer KAWSGjngsnasoNBI320933'
            ])->post('https://bullspay-sooty.vercel.app/api/send-notification', [
                'email' => $getTransaction->payment_offers[0]->offer->product->user->email,
                'title' => 'Uma venda pix foi paga!',
                'message' => 'Sua comissão foi de R$ ' . number_format($getTransaction->value_to_receiver / 100, 2, ',', '.')
            ]);
    
            if (!$notificationResponse->successful()) {
                Log::error('Falha ao enviar notificação', [
                    'status' => $notificationResponse->status(),
                    'response' => $notificationResponse->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação: ' . $e->getMessage());
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }

    public function transactions_rapdyn(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required',
            'notification_type' => 'required',
            'status' => 'required',
        ]);

        $getTransaction = CelcashPayments::where('galax_pay_id', $validatedData['id'])
            ->with('payment_offers', function($query) {
                $query->where('type', 'principal')
                    ->with('offer', function($query) {
                        $query->with('product', function($query) {
                            $query->with('user:id,email');
                            $query->select(['id', 'email_support', 'product_name', 'user_id']);
                        })
                        ->select(['id', 'product_id', 'utmify_token']);
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
                $validatedData['status'] == 'paid'
            ) {
                $status = 'payed_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['status'] == 'pending'
            ) {
                $status = 'pending_pix';
                $isPayedStatus = false;
            }

            /* if (
                $validatedData['status'] == 'med'
            ) {
                $status = 'chargeback';
                $isPayedStatus = false;
            } */

            /* if (
                $validatedData['status'] == 'blocked'
            ) {
                $status = 'chargeback';
                $isPayedStatus = false;
            } */

            if (
                $validatedData['status'] == 'returned'
            ) {
                $status = 'refunded';
                $isPayedStatus = false;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                $data = [
                    'name' => $getTransaction->buyer_name ?? "Bulls Pay",
                    'email' => $getTransaction->buyer_email ?? "compras@bullspay.com.br",
                    'password' => $generateRandomPassword
                ];

                $configsData = Configuration::first();

                $data['withdrawal_period'] = $configsData->default_withdraw_period ?? 0;
                $data['withdrawal_tax'] = $configsData->default_withdraw_tax ?? 1.5;
                $data['pix_tax_value'] = $configsData->default_pix_tax_value ?? 1.99;
                $data['pix_money_tax_value'] = $configsData->default_pix_money_tax_value ?? 1.3;
                $data['card_tax_value'] = $configsData->default_card_tax_value ?? 4.99;
                $data['card_money_tax_value'] = $configsData->default_card_money_tax_value ?? 1.5;
                $data['cash_in_adquirer_name'] = $configsData->default_cash_in_adquirer ?? 'zendry';
                $data['cash_out_adquirer_name'] = $configsData->default_cash_out_adquirer ?? 'zendry';

                Log::info("| CRIÇÃO DE CONTA - COMPRADOR ".'| Criando conta de usuário comprador', ['Dados da criação do usuário' => $data]);

                try {
                    $buyerUser = User::create($data);
                }
                catch (\Exception $e) {
                    Log::error('Erro ao criar o usuário pagante', ["erro" => $e->getMessage(), "data" => $data]);

                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['message']['reference_code'],
                        'webhook_event' => $validatedData['message'],
                        'webhook_data' => 'erro',
                    ]);
                }
            }

            $getTransaction->update([
                'status' => $status,
                'buyer_user_id' => $buyerUser->id
            ]);

            try {
                Mail::to($buyerUser->email)->send(new BuyerMail($buyerUser->name, $buyerUser->email, $getTransaction->payment_offers[0]->offer->product->email_support, $validatedData['id']));
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
            
            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['id'])
                ->where('status', 'Waiting Payment')
                ->get();

            if($pendingEvents->isNotEmpty()  ) {
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
            if($getTransaction->payment_offers[0]->offer->utmify_token) {
                try {
                    $body = [
                        "orderId" => $validatedData['id'],
                        "platform" => "BullsPay",
                        "paymentMethod" => "pix",
                        "status" => "paid",
                        "createdAt" => $getTransaction->created_at,
                        "approvedDate" => $getTransaction->updated_at,
                        "refundedAt" => null,
                        "customer" => [
                            "name" => $getTransaction->buyer_name,
                            "email" => $getTransaction->buyer_email,
                            "phone" => null,
                            "document" => $getTransaction->buyer_document_cpf,
                            "country" => "BR",
                            "IP" =>null
                        ],
                        "products" =>[
                            [
                                "id" => $getTransaction->galax_pay_id,
                                "name" => $getTransaction->payment_offers[0]->offer->product->product_name,
                                "planId" => null,
                                "planName" => null,
                                "quantity" => 1,
                                "priceInCents" => $getTransaction->payment_offers[0]->offer->price * 100
                            ]
                        ],
                        "trackingParameters" => [
                            "src" => $getTransaction->src,
                            "sck"=>  $getTransaction->sck,
                            "utm_source"=>  $getTransaction->utm_source,
                            "utm_campaign"=> $getTransaction->utm_campaign,
                            "utm_medium"=> $getTransaction->utm_medium,
                            "utm_content"=> $getTransaction->utm_content,
                            "utm_term"=> $getTransaction->utm_term,
                        ],
                        "commission" => [
                            "totalPriceInCents" => $getTransaction->total_value,
                            "gatewayFeeInCents" => $getTransaction->value_to_platform,
                            "userCommissionInCents" => $getTransaction->value_to_receiver
                        ],
                        "isTest" => false
                    ];
                    $headers = [
                        'x-api-token' => $getTransaction->payment_offers[0]->offer->utmify_token
                    ];
                    $utmify =Http::WithHeaders($headers)
                        ->post(
                            'https://api.utmify.com.br/api-credentials/orders',
                            $body
                        );
                    Log::info("Evento enviado ao UTMIFY ", ["Response" => $utmify]);
                 } catch (\Exception $e) {
                    Log::error("Erro ao enviar Request para UTMIFY", ["erro" => $e->getMessage()]);
                }
            }

        try {
            $notificationResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer KAWSGjngsnasoNBI320933'
            ])->post('https://bullspay-sooty.vercel.app/api/send-notification', [
                'email' => $getTransaction->payment_offers[0]->offer->product->user->email,
                'title' => 'Uma venda pix foi paga!',
                'message' => 'Sua comissão foi de R$ ' . number_format($getTransaction->value_to_receiver / 100, 2, ',', '.')
            ]);
    
            if (!$notificationResponse->successful()) {
                Log::error('Falha ao enviar notificação', [
                    'status' => $notificationResponse->status(),
                    'response' => $notificationResponse->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação: ' . $e->getMessage());
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }

    public function transactions_super(Request $request)
    {
        $validatedData = $request->validate([
            'event' => 'required',
            'data' => 'required',
            'data.id' => 'required',
            'data.status' => 'required',
        ]);

        $getTransaction = CelcashPayments::where('galax_pay_id', $validatedData['data']['id'])
            ->with('payment_offers', function($query) {
                $query->where('type', 'principal')
                    ->with('offer', function($query) {
                        $query->with('product', function($query) {
                            $query->with('user:id,email');
                            $query->select(['id', 'email_support', 'product_name', 'user_id']);
                        })
                        ->select(['id', 'product_id', 'utmify_token']);
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
                $validatedData['data']['status'] == 'SUCCEEDED'
            ) {
                $status = 'payed_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['data']['status'] == 'PENDING'
            ) {
                $status = 'pending_pix';
                $isPayedStatus = false;
            }

            /* if (
                $validatedData['data']['status'] == 'CHARGEBACK'
            ) {
                $status = 'chargeback';
                $isPayedStatus = false;
            } */

            if (
                $validatedData['data']['status'] == 'REFUNDED'
            ) {
                $status = 'refunded';
                $isPayedStatus = false;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                $data = [
                    'name' => $getTransaction->buyer_name ?? "Bulls Pay",
                    'email' => $getTransaction->buyer_email ?? "compras@bullspay.com.br",
                    'password' => $generateRandomPassword
                ];

                $configsData = Configuration::first();

                $data['withdrawal_period'] = $configsData->default_withdraw_period ?? 0;
                $data['withdrawal_tax'] = $configsData->default_withdraw_tax ?? 1.5;
                $data['pix_tax_value'] = $configsData->default_pix_tax_value ?? 1.99;
                $data['pix_money_tax_value'] = $configsData->default_pix_money_tax_value ?? 1.3;
                $data['card_tax_value'] = $configsData->default_card_tax_value ?? 4.99;
                $data['card_money_tax_value'] = $configsData->default_card_money_tax_value ?? 1.5;
                $data['cash_in_adquirer_name'] = $configsData->default_cash_in_adquirer ?? 'zendry';
                $data['cash_out_adquirer_name'] = $configsData->default_cash_out_adquirer ?? 'zendry';

                Log::info("| CRIÇÃO DE CONTA - COMPRADOR ".'| Criando conta de usuário comprador', ['Dados da criação do usuário' => $data]);

                try {
                    $buyerUser = User::create($data);
                }
                catch (\Exception $e) {
                    Log::error('Erro ao criar o usuário pagante', ["erro" => $e->getMessage(), "data" => $data]);

                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['message']['reference_code'],
                        'webhook_event' => $validatedData['message'],
                        'webhook_data' => 'erro',
                    ]);
                }
            }

            $getTransaction->update([
                'status' => $status,
                'buyer_user_id' => $buyerUser->id
            ]);

            try {
                Mail::to($buyerUser->email)->send(new BuyerMail($buyerUser->name, $buyerUser->email, $getTransaction->payment_offers[0]->offer->product->email_support, $validatedData['id']));
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

            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['data']['id'])
            ->where('status', 'Waiting Payment')
            ->get();

        if($pendingEvents->isNotEmpty()  ) {
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
        if($getTransaction->payment_offers[0]->offer->utmify_token) {
            try {
                $body = [
                    "orderId" => $validatedData['id'],
                    "platform" => "BullsPay",
                    "paymentMethod" => "pix",
                    "status" => "paid",
                    "createdAt" => $getTransaction->created_at,
                    "approvedDate" => $getTransaction->updated_at,
                    "refundedAt" => null,
                    "customer" => [
                        "name" => $getTransaction->buyer_name,
                        "email" => $getTransaction->buyer_email,
                        "phone" => null,
                        "document" => $getTransaction->buyer_document_cpf,
                        "country" => "BR",
                        "IP" =>null
                    ],
                    "products" =>[
                        [
                            "id" => $getTransaction->galax_pay_id,
                            "name" => $getTransaction->payment_offers[0]->offer->product->product_name,
                            "planId" => null,
                            "planName" => null,
                            "quantity" => 1,
                            "priceInCents" => $getTransaction->payment_offers[0]->offer->price * 100
                        ]
                    ],
                    "trackingParameters" => [
                        "src" => $getTransaction->src,
                        "sck"=>  $getTransaction->sck,
                        "utm_source"=>  $getTransaction->utm_source,
                        "utm_campaign"=> $getTransaction->utm_campaign,
                        "utm_medium"=> $getTransaction->utm_medium,
                        "utm_content"=> $getTransaction->utm_content,
                        "utm_term"=> $getTransaction->utm_term,
                    ],
                    "commission" => [
                        "totalPriceInCents" => $getTransaction->total_value,
                        "gatewayFeeInCents" => $getTransaction->value_to_platform,
                        "userCommissionInCents" => $getTransaction->value_to_receiver
                    ],
                    "isTest" => false
                ];
                $headers = [
                    'x-api-token' => $getTransaction->payment_offers[0]->offer->utmify_token
                ];
                $utmify =Http::WithHeaders($headers)
                    ->post(
                        'https://api.utmify.com.br/api-credentials/orders',
                        $body
                    );
                Log::info("Evento enviado ao UTMIFY ", ["Response" => $utmify]);
                } catch (\Exception $e) {
                Log::error("Erro ao enviar Request para UTMIFY", ["erro" => $e->getMessage()]);
            }
        }

        try {
            $notificationResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer KAWSGjngsnasoNBI320933'
            ])->post('https://bullspay-sooty.vercel.app/api/send-notification', [
                'email' => $getTransaction->payment_offers[0]->offer->product->user->email,
                'title' => 'Uma venda pix foi paga!',
                'message' => 'Sua comissão foi de R$ ' . number_format($getTransaction->value_to_receiver / 100, 2, ',', '.')
            ]);
    
            if (!$notificationResponse->successful()) {
                Log::error('Falha ao enviar notificação', [
                    'status' => $notificationResponse->status(),
                    'response' => $notificationResponse->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação: ' . $e->getMessage());
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }

    public function transactions_venit(Request $request)
    {
        /*$validatedData = $request->validate([
            'message' => 'required',
            'message.reference_code' => 'required',
            'message.status' => 'required',
            'message.end_to_end' => 'required',
            'message.value_cents' => 'required'
        ]);*/

        $validatedData = $request->validate([
            'data.transactionId' => 'required',
            'data.status' => 'required',
            'data.endToEndId' => 'required',
            'data.amount' => 'required'
        ]);

        $getTransaction = CelcashPayments::where('galax_pay_id', $validatedData['data']['transactionId'])
            ->with('payment_offers', function($query) {
                $query->where('type', 'principal')
                    ->with('offer', function($query) {
                        $query->with('product', function($query) {
                            $query->with('user:id,email');
                            $query->select(['id', 'email_support', 'product_name', 'user_id']);
                        })
                        ->select(['id', 'product_id', 'utmify_token']);
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
                $validatedData['data']['status'] == 'paid'
            ) {
                $status = 'payed_pix';
                $isPayedStatus = true;
            }

            if (
                $validatedData['data']['status'] == 'awaiting_payment'
            ) {
                $status = 'pending_pix';
                $isPayedStatus = false;
            }
        }

        if ($isPayedStatus) {
            $buyerUser = User::where('email', $getTransaction->buyer_email)->first();

            if (!$buyerUser) {
                $generateRandomPassword = UserService::generateRandomPassword();

                $data = [
                    'name' => $getTransaction->buyer_name,
                    'email' => $getTransaction->buyer_email,
                    'password' => $generateRandomPassword
                ];

                $configsData = Configuration::first();

                $data['withdrawal_period'] = $configsData->default_withdraw_period ?? 0;
                $data['withdrawal_tax'] = $configsData->default_withdraw_tax ?? 1.5;
                $data['pix_tax_value'] = $configsData->default_pix_tax_value ?? 1.99;
                $data['pix_money_tax_value'] = $configsData->default_pix_money_tax_value ?? 1.3;
                $data['card_tax_value'] = $configsData->default_card_tax_value ?? 4.99;
                $data['card_money_tax_value'] = $configsData->default_card_money_tax_value ?? 1.5;
                $data['cash_in_adquirer_name'] = $configsData->default_cash_in_adquirer ?? 'venit';
                $data['cash_out_adquirer_name'] = $configsData->default_cash_out_adquirer ?? 'venit';

                Log::info("| CRIÇÃO DE CONTA - COMPRADOR ".'| Criando conta de usuário comprador', ['Dados da criação do usuário' => $data]);

                try {
                    $buyerUser = User::create($data);
                }
                catch (\Exception $e) {
                    \App\Models\CelcashWebhook::create([
                        'webhook_title' => 'ERRO AO CRIAR USUÁRIO PAGANTE',
                        'webhook_id' => $validatedData['data']['transactionId'],
                        'webhook_event' => $validatedData,
                        'webhook_data' => $request,
                    ]);
                }
            }

            $getTransaction->update([
                'status' => $status,
                'buyer_user_id' => $buyerUser->id
            ]);

            try {
                Mail::to($buyerUser->email)->send(new BuyerMail($buyerUser->name, $buyerUser->email, $getTransaction->payment_offers[0]->offer->product->email_support, $validatedData['data']['transactionId']));
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
            $pendingEvents = PendingPixelEvents::where('payment_id', $validatedData['data']['transactionId'])
                ->where('status', 'Waiting Payment')
                ->get();

            if($pendingEvents->isNotEmpty()  ) {
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
            if($getTransaction->payment_offers[0]->offer->utmify_token) {
                try {
                    $body = [
                        "orderId" => $validatedData['data']['transactionId'],
                        "platform" => "BullsPay",
                        "paymentMethod" => "pix",
                        "status" => "paid",
                        "createdAt" => $getTransaction->created_at,
                        "approvedDate" => $getTransaction->updated_at,
                        "refundedAt" => null,
                        "customer" => [
                            "name" => $getTransaction->buyer_name,
                            "email" => $getTransaction->buyer_email,
                            "phone" => null,
                            "document" => $getTransaction->buyer_document_cpf,
                            "country" => "BR",
                            "IP" =>null
                        ],
                        "products" =>[
                            [
                                "id" => $getTransaction->galax_pay_id,
                                "name" => $getTransaction->payment_offers[0]->offer->product->product_name,
                                "planId" => null,
                                "planName" => null,
                                "quantity" => 1,
                                "priceInCents" => $getTransaction->payment_offers[0]->offer->price * 100
                            ]
                        ],
                        "trackingParameters" => [
                            "src" => $getTransaction->src,
                            "sck"=>  $getTransaction->sck,
                            "utm_source"=>  $getTransaction->utm_source,
                            "utm_campaign"=> $getTransaction->utm_campaign,
                            "utm_medium"=> $getTransaction->utm_medium,
                            "utm_content"=> $getTransaction->utm_content,
                            "utm_term"=> $getTransaction->utm_term,
                        ],
                        "commission" => [
                            "totalPriceInCents" => $getTransaction->total_value,
                            "gatewayFeeInCents" => $getTransaction->value_to_platform,
                            "userCommissionInCents" => $getTransaction->value_to_receiver
                        ],
                        "isTest" => false
                    ];
                    $headers = [
                        'x-api-token' => $getTransaction->payment_offers[0]->offer->utmify_token
                    ];
                    $utmify =Http::WithHeaders($headers)
                        ->post(
                            'https://api.utmify.com.br/api-credentials/orders',
                            $body
                        );
                    Log::info("Evento enviado ao UTMIFY ", ["Response" => $utmify]);
                } catch (\Exception $e) {
                    Log::error("Erro ao enviar Request para UTMIFY", ["erro" => $e->getMessage()]);
                }
            }



            event(new CoursePurchased($buyerUser->id, $getTransaction->galax_pay_id));
        } else {
            $getTransaction->update([
                'status' => $status
            ]);
        }

        try {
            $notificationResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer KAWSGjngsnasoNBI320933'
            ])->post('https://bullspay-sooty.vercel.app/api/send-notification', [
                'email' => $getTransaction->payment_offers[0]->offer->product->user->email,
                'title' => 'Uma venda pix foi paga!',
                'message' => 'Sua comissão foi de R$ ' . number_format($getTransaction->value_to_receiver / 100, 2, ',', '.')
            ]);
    
            if (!$notificationResponse->successful()) {
                Log::error('Falha ao enviar notificação', [
                    'status' => $notificationResponse->status(),
                    'response' => $notificationResponse->json()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação: ' . $e->getMessage());
        }

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }
}
