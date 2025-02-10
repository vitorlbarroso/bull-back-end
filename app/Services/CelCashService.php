<?php

namespace App\Services;

use App\ExternalApis\CelCash\AuthenticationRequests;
use App\ExternalApis\CelCash\CnpjUsersRequests;
use App\ExternalApis\CelCash\CpfUsersRequests;
use App\ExternalApis\CelCash\PaymentsRequest;
use App\Http\Helpers\Responses;
use App\Models\CelcashPaymentsGatewayData;
use App\Models\User;
use App\Models\UserCelcashCpfCredentials;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CelCashService
{
    static public function getToken()
    {
        try {
            $getToken = CelcashPaymentsGatewayData::first();

            if (!$getToken) {
                Log::info('Token da celcash não foi localizado ou está expirado!');

                return [
                    'error' => [
                        'message' => "Token não localizado ou expirado",
                        'errorCode' => 1200
                    ]
                ];
            }

            return $getToken;
        }
        catch (\Exception $e) {
            Log::error('Erro ao buscar o token da celcash: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao buscar token celcash",
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function generateToken()
    {
        $clientId = env('CELCASH_GALAX_ID');
        $clientSecret = env('CELCASH_GALAX_HASH');
        $baseUrl = env('CELCASH_BASE_URL');
        $certPath = env('CELCASH_CERTIFICATE_PATH');
        $certCrtPath = env('CELCASH_CERTIFICATE_CRT_PATH');
        $certPassphrase = env('CELCASH_CERTIFICATE_PASSPHRASE');

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $body = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
        ];

        try {
            $response = HTTP::withHeaders($headers)
                ->withOptions([
                    'cert' => [$certPath, $certPassphrase],
                    'verify' => false
                ])
                ->post($baseUrl . "/oauth/token", $body);

            $data = $response->json();

            return $data;

            $accessToken = $data['access_token'];
            $expiresIn = Carbon::now()->addMinutes(3);

            CelcashPaymentsGatewayData::updateOrCreate(
                ['id' => 1],
                [
                    'token' => $accessToken,
                    'expires_in' => $expiresIn,
                ]
            );
        }
        catch(\Exception $e) {
            Log::error('Erro ao atualizar token de requisição API: ' . $e->getMessage());
            return 'Erro: ' . $e->getMessage();
        }
    }

    static public function getSubaccountToken($galaxId, $galaxToken)
    {
        $getToken = AuthenticationRequests::get_subaccount_token($galaxId, $galaxToken);

        return $getToken;
    }

    static public function createUserCpf($data)
    {
        /* Validando itens */
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'document' => 'required|string',
            'phone' => 'required|string',
            'address_zipcode' => 'required|string',
            'address_street' => 'required|string',
            'address_number' => 'required|string',
            'address_neighborhood' => 'required|string',
            'address_city' => 'required|string',
            'address_state' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Ocorreu um erro ao validar os itens recebidos no método: ' . $validator->errors(), );

            return [
                'error' => [
                    'message' => "Itens insuficientes",
                    'errors' => $validator->errors(),
                    'errorCode' => 1100
                ]
            ];
        }

        $validated = $validator->validated();

        /* Buscando token */
        $getToken = self::getToken();

        if ($getToken['error'])
            return $getToken;

        /* Setando novos valores dentro do array de valores validados */
        $user = Auth::user();

        $validated['token'] = $getToken;
        $validated['user'] = $user;

        /* Fazendo requisição para criação de usuário */
        $createUser = CpfUsersRequests::createUserCpf($validated);

        return $createUser;
    }

    static public function createUserCnpj($data)
    {
        /* Validando itens */
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'document_cpf' => 'required|string',
            'document_cnpj' => 'required|string',
            'name_display' => 'required|string',
            'phone' => 'required|string',
            'cnae' => 'required|string',
            'type_company_cnpj' => 'required|string',
            'address_zipcode' => 'required|string',
            'address_street' => 'required|string',
            'address_number' => 'required|string',
            'address_neighborhood' => 'required|string',
            'address_city' => 'required|string',
            'address_state' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Ocorreu um erro ao validar os itens recebidos no método: ' . $validator->errors(), );

            return [
                'error' => [
                    'message' => "Itens insuficientes",
                    'errors' => $validator->errors(),
                    'errorCode' => 1100
                ]
            ];
        }

        $validated = $validator->validated();

        /* Buscando token */
        $getToken = self::getToken();

        if ($getToken['error'])
            return $getToken;

        /* Setando novos valores dentro do array de valores validados */
        $user = Auth::user();

        $validated['token'] = $getToken;
        $validated['user'] = $user;

        /* Fazendo requisição para criação de usuário */
        $createUser = CnpjUsersRequests::createUserCnpj($validated);

        return $createUser;
    }

    static public function generatePaymentPix($data)
    {
        $validator = Validator::make($data, [
            'my_id' => 'required|string',
            'value' => 'required|integer',
            'payday' => 'required|string',
            'customer_name' => 'required|string',
            'customer_document' => 'required|string',
            'customer_email' => 'required|string',
            'customer_phone' => 'required|string',
            'split_galax_pay_id' => 'required|integer',
            'split_value' => 'required|integer',
        ]);

        if ($validator->fails()) {
            Log::error('Ocorreu um erro ao validar os itens recebidos no método: ' . $validator->errors(), );

            return [
                'error' => [
                    'message' => "Itens insuficientes",
                    'errors' => $validator->errors(),
                    'errorCode' => 1100
                ]
            ];
        }

        $validated = $validator->validated();

        /* Buscando token */
        $getToken = self::getToken();

        if ($getToken['error'])
            return $getToken;

        $validated['token'] = $getToken;

        $generatePayment = PaymentsRequest::generatePaymentPix($validated);

        return $generatePayment;
    }

    static public function calculateTax($paymentType, $userId, $totalPrice)
    {
        $user = User::where('id', $userId)->first();

        if ($paymentType == 'pix') {
            $taxValue = $user->pix_tax_value;
            $moneyTaxValue = $user->pix_money_tax_value * 100;
        }

        $taxAmount = ($taxValue / 100) * $totalPrice;

        $finalPrice = $totalPrice - $taxAmount - $moneyTaxValue;

        return round($finalPrice);
    }
}
