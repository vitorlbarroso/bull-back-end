<?php

namespace App\ExternalApis\CelCash;

use App\Services\CelCashService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentsRequest
{
    static public function generatePaymentPix($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $data['token']['token'],
            'Content-Type' => 'application/json'
        ];

        $body = [
            'calendario' => [
                'expiracao' => 86400
            ],
            'valor' => [
                'original' => $data['valor']['original'],
                'modalidadeAlteracao' => 0
            ],
            'chave' => $data['chave'],
        ];

        $baseUrl = env('CELCASH_BASE_URL');
        $certPath = env('CELCASH_CERTIFICATE_PATH');
        $certCrtPath = env('CELCASH_CERTIFICATE_CRT_PATH');
        $certPassphrase = env('CELCASH_CERTIFICATE_PASSPHRASE');

        try {
            $createPayment = Http::WithHeaders($headers)
                ->withOptions([
                    'cert' => [$certPath, $certPassphrase],
                    'verify' => false
                ])
                ->post(
                    $baseUrl . '/cob',
                    $body
                );

            $response = $createPayment->json();

            if (isset($response['message']) && $response['message'] === 'Unauthorized') {
                CelCashService::generateToken();

                $createPayment = Http::WithHeaders($headers)
                    ->withOptions([
                        'cert' => [$certPath, $certPassphrase],
                        'verify' => false
                    ])
                    ->post(
                        $baseUrl . '/cob',
                        $body
                    );

                $response = $createPayment->json();

                if (isset($response['message']) && $response['message'] === 'Unauthorized') {
                    return [
                        'error' => [
                            'message' => "Erro ao gerar pagamento pix na voluti",
                            'errorMessage' => $response,
                            'errorCode' => 1100
                        ]
                    ];
                }

                return $response;
            }

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar gerar pagamento pix na voluti: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao gerar pagamento pix na voluti",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }
}
