<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCpfCredentials;
use Illuminate\Support\Facades\Auth;

class UserService
{
    static public function getPayConfigsPendences($data = false)
    {
        if (!$data) {
            $user = Auth::user();
        } else {
            $user = User::where('id', $data['id'])->first();
        }

        if ($user->account_type->value == 'PF') {
            $getCredentials = UserCelcashCpfCredentials::where('user_id', $user->id)->with('documents')->first();
        } else {
            $getCredentials = UserCelcashCnpjCredentials::where('user_id', $user->id)->with('documents')->first();
        }

        $specialDocument = $user->account_type->value == 'PF' ? 'rg_address_media' : 'company_document_media';

        if (!$getCredentials) {
            return [
                'configs_account' => true,
                'documents' => [
                    'rg_selfie',
                    'rg_front',
                    'rg_back',
                    $specialDocument
                ]
            ];
        }

        if (!$getCredentials->documents) {
            return [
                'configs_account' => false,
                'documents' => [
                    'rg_selfie',
                    'rg_front',
                    'rg_back',
                    $specialDocument
                ]
            ];
        }

        return false;
    }

    static public function generateRandomPassword($length = 14) : string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';

        $password = substr(str_shuffle($uppercase), 0, 1);
        $password .= substr(str_shuffle($lowercase), 0, 1);
        $password .= substr(str_shuffle($numbers), 0, 1);

        $remainingLength = $length - strlen($password);
        $allCharacters = $uppercase . $lowercase . $numbers;
        $password .= substr(str_shuffle($allCharacters), 0, $remainingLength);

        return str_shuffle($password);
    }
}
