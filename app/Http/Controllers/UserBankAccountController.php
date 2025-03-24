<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Bank\CreateUserBankAccountRequest;
use App\Models\BanksCode;
use App\Models\CelcashPayments;
use App\Models\UserBankAccount;
use App\Models\WithdrawalRequests;
use App\Services\UserPaymentsDataService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBankAccountController extends Controller
{
    public function create_account(CreateUserBankAccountRequest $request)
    {
        $validated = $request->validated();

        $user = Auth::user();

        $getBankCode = BanksCode::where('id', $validated['banks_codes_id'])->exists();

        $accountCheckDigit = $validated['account_check_digit'] ? $validated['account_check_digit'] : null;

        if (!$getBankCode) {
            return Responses::ERROR('Código do banco não localizado!', null, 1100, 400);
        }

        try {
            $getAllAccounts = UserBankAccount::where('user_id', $user->id);
            if ($getAllAccounts->exists()) {
                $getAllAccounts->update(['is_active' => false]);
            }

            $createAccountBank = UserBankAccount::create([
                'user_id' => $user->id,
                'banks_codes_id' => $validated['banks_codes_id'],
                'responsible_name' => $validated['responsible_name'],
                'responsible_document' => $validated['responsible_document'],
                'account_type' => $validated['account_type'],
                'account_number' => $validated['account_number'],
                'account_agency' => $validated['account_agency'],
                'account_check_digit' => $validated['account_check_digit'],
                'pix_type_key' => $validated['pix_type_key'],
                'pix_key' => $validated['pix_key'],
                'status' => 'approved',
                'is_active' => true,
            ]);

            return Responses::SUCCESS('Conta bancária em análise!');
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao tentar criar a conta bancária!', $e->getMessage(), 1100);
        }
    }

    public function get_accounts()
    {
        $user = Auth::user();

        $getUserAccounts = UserBankAccount::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->orderBy('id', 'desc')
            ->with('banks_code:id,name,code')
            ->get(['id', 'user_id', 'banks_codes_id', 'responsible_name', 'responsible_document', 'account_type', 'account_number', 'account_agency', 'account_check_digit', 'pix_type_key', 'pix_key', 'status', 'is_active']);

        return Responses::SUCCESS('', $getUserAccounts);
    }

    public function update_status($id)
    {
        $user = Auth::user();

        $getAccountBank = UserBankAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->first();

        if (!$getAccountBank) {
            return Responses::ERROR('Conta bancária não localizada!', null, 1100, 400);
        }

        try {
            DB::transaction(function () use ($id, $getAccountBank, $user) {
                $updateAllAcounts = UserBankAccount::where('user_id', $user->id)
                    ->update(['is_active' => 0]);

                $updateAccountId = $getAccountBank->update(['is_active' => 1]);
            });

            return Responses::SUCCESS('Status atualizado com sucesso!');
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao tentar atualizar a conta bancária ativa', $e->getMessage(), 1100);
        }
    }

    public function delete_account($id)
    {
        $user = Auth::user();

        $getAccountBank = UserBankAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$getAccountBank) {
            return Responses::ERROR('Conta bancária não localizada!', null, 1100, 400);
        }

        $getAccountBank->update([
            'is_deleted' => true
        ]);

        return Responses::SUCCESS('Conta bancária deletada com sucesso!');
    }
}
