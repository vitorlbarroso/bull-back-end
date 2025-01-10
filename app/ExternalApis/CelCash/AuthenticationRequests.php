<?php

namespace App\ExternalApis\CelCash;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthenticationRequests
{
    static public function get_subaccount_token($galaxId, $galaxHash)
    {
        $baseUrl = env('CELCASH_BASE_URL');

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($galaxId . ':' . $galaxHash),
            'Content-Type' => 'application/json',
        ];

        $body = [
            'grant_type' => 'authorization_code',
            'scope' => 'company.write company.read'
        ];

        try {
            $response = HTTP::withHeaders($headers)->post($baseUrl . "/v2/token", $body);

            return $response->json();
        }
        catch (\Exception $e) {
            Log::error('Erro ao buscar token de autenticação na cel ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Usuário cadastrado mas sem documentos enviados para análise!",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }
}
