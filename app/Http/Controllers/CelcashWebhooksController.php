<?php

namespace App\Http\Controllers;

use App\Events\CoursePurchased;
use App\Http\Helpers\Responses;
use App\Http\Requests\CelCashGateway\Webhooks\UpdatePaymentRequest;
use App\Http\Requests\CelCashGateway\Webhooks\VerifyDocumentsRequest;
use App\Models\CelcashConfirmHashWebhook;
use App\Models\CelcashPayments;
use App\Models\User;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCpfCredentials;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $data = $validatedData['data'];

        $getTransaction = CelcashPayments::where('galax_pay_id', $data['txId'])
            ->first();

        if (!$getTransaction) {
            return Responses::ERROR('Transação não localizada!', null, 1300, 400);
        }

        $isPayedStatus = false;

        if ($getTransaction->type == 'pix') {
            switch ($data['status']) {
                case 'LIQUIDATED':
                    $status = 'payed_pix';
                    $isPayedStatus = true;
                    break;

                default:
                    $status = 'pending_pix';
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

            /*
             * ========================================================
             * ADICIONAR ENVIO DE E-MAIL DE COMPRA PARA O USUÁRIO
             * ========================================================
             * */

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

        return Responses::SUCCESS('Status do transação atualizado com sucesso!', null, 200);
    }
}
