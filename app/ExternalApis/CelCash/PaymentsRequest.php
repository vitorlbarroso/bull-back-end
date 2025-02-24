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
            'x-authorization-key' => $data['token']['token'],
            'Content-Type' => 'application/json'
        ];

        $body = [
            'isInfoProducts' => true,
            'paymentMethod' => 'pix',
            'customer' => [
                'name' => $data['customer']['name'],
                'email' => $data['customer']['email'],
                'document' => [
                    'number' => $data['customer']['document']['number'],
                    'type' => $data['customer']['document']['type'],
                ],
            ],
            'items' => [
                [
                    'title' => 'Compra*BullsPay',
                    'description' => 'Compra*BullsPay',
                    'unitPrice' => $data['price'],
                    'quantity' => 1,
                    'tangible' => false,
                ]
            ],
            'postback_url' => env('WEBHOOKS_BASE_URL')
        ];

        $baseUrl = env('CELCASH_BASE_URL');

        try {
            $createPayment = Http::WithHeaders($headers)
                ->post(
                    $baseUrl . '/transactions',
                    $body
                );

            $response = $createPayment->json();

            if (isset($response['error'])) {
                return [
                    'error' => [
                        'message' => "Erro ao gerar pagamento pix na adquirente",
                        'errorMessage' => $response,
                        'errorCode' => 1100
                    ]
                ];
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
