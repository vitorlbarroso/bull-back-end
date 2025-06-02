<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Withdraws\RequestWithdrawalRequest;
use App\Models\UserBankAccount;
use App\Models\WithdrawalRequests;
use App\Services\UserPaymentsDataService;
use App\Services\UserService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class WithdrawalRequestsController extends Controller
{
    public function get_withdraw_infos()
    {
        $getWithdrawInfos = UserPaymentsDataService::getWithdrawalData();

        return Responses::SUCCESS('', $getWithdrawInfos);
    }

    public function withdraws_requests(Request $request)
    {
        $user = Auth::user();

        $items_per_page = $request->query('items_per_page', 10);

        $getAllWithdrawalRequests = WithdrawalRequests::where('user_id', $user->id)
            ->with('account_bank', function ($query) {
                $query->with('banks_code', function ($query) {
                    $query->select('id', 'name', 'code');
                })
                    ->select('id', 'user_id', 'banks_codes_id');
            })
            ->orderBy('id', 'desc')
            ->select('id', 'user_id', 'user_bank_accounts_id', 'withdrawal_amount', 'status', 'tax_value', 'central_bank_unic_id', 'created_at')
            ->paginate($items_per_page);

        return Responses::SUCCESS('', $getAllWithdrawalRequests);
    }

    public function request_withdrawal(RequestWithdrawalRequest $request)
    {
        $validated = $request->validated();

        if ($validated['amount'] < 1000) {
            return Responses::ERROR('Valor não permitido, saque mínimo de 10 reais!', null, 1100, 400);
        }

        $user = Auth::user();

        // Usando lock para prevenir race conditions
        return DB::transaction(function () use ($validated, $user) {
            // Lock o usuário para garantir que apenas uma requisição seja processada por vez
            $user = User::lockForUpdate()->find($user->id);

            $getUserPayPendences = UserService::getPayConfigsPendences($user);

            if ($getUserPayPendences || $user->is_blocked) {
                return Responses::ERROR('Esse usuário não está autorizado a realizar saques', $getUserPayPendences, 1200, 400);
            }

            $getTotalAuthorizedWithdrawal = UserPaymentsDataService::getWithdrawalData();

            if (($getTotalAuthorizedWithdrawal['total_available'] * 100) < $validated['amount']) {
                return Responses::ERROR('O valor solicitado é maior que o disponível para saque!', $getTotalAuthorizedWithdrawal['total_available'], 1300, 400);
            }

            $getUserActiveAccount = UserBankAccount::where('user_id', $user->id)
                ->where('is_active', 1)
                ->first();

            if (!$getUserActiveAccount) {
                return Responses::ERROR('O usuário não possui uma conta bancária cadastrada!', null, 1400, 400);
            }

            Log::info('Dados do usuário que solicitou saque: ', ['user' => $user, 'auto_withdrawal' => $user->auto_withdrawal]);
            
            try {
                $createWithdrawalRequest = WithdrawalRequests::create([
                    'user_id' => $user->id,
                    'user_bank_accounts_id' => $getUserActiveAccount->id,
                    'withdrawal_amount' => $validated['amount'],
                    'status' => 'pending',
                    'tax_value' => $user->withdrawal_tax
                ]);

                if ($user->auto_withdrawal) {
                    $adminBaseUrl = env('ADMIN_BASE_URL');
                    $xApiToken = env('XATK');

                    $headers = [
                        'Content-Type' => 'application/json'
                    ];

                    $body = [
                        'withdrawal_id' => $createWithdrawalRequest->id,
                        'x_api_token' => $xApiToken,
                    ];

                    try {
                        // Adiciona um pequeno delay para garantir que a transação foi processada
                        sleep(1);

                        $sendAutoApprove = Http::WithHeaders($headers)
                            ->timeout(10) // Adiciona timeout de 30 segundos
                            ->post(
                                env('ADMIN_BASE_URL') . '/system/wdal/wdal_update',
                                $body
                            );

                        $response = $sendAutoApprove->json();

                        if (!$sendAutoApprove->successful()) {
                            Log::error('Falha na requisição de autowithdrawal', [
                                'status' => $sendAutoApprove->status(),
                                'response' => $response
                            ]);
                            throw new \Exception('Falha na requisição de autowithdrawal');
                        }

                        Log::info('Resposta da requisição para autowithdrawal recebida: ', ['response' => $response]);
                    }
                    catch (\Exception $e) {
                        Log::error('Erro na requisição de autowithdrawal: ' . $e->getMessage(), [
                            'withdrawal_id' => $createWithdrawalRequest->id,
                            'exception' => $e
                        ]);
                        // Não propaga o erro para não reverter a transação
                    }
                }

                return Responses::SUCCESS('Solicitação de saque criada com sucesso!');
            }
            catch (\Exception $e) {
                Log::error('Não foi possível solicitar um saque para o usuário', ['error' => $e->getMessage()]);
                throw $e; // Propaga a exceção para que a transação seja revertida

                return Responses::ERROR('Não foi possível solicitar um saque para o usuário', 'Uma solicitação já está sendo processada!', 1500, 400);
            }
        });
    }
}
