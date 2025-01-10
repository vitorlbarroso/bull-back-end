<?php

namespace App\ExternalApis\CelCash;

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
            'myId' => $data['my_id'],
            'value' => $data['value'],
            'payday' => $data['payday'],
            'mainPaymentMethodId' => 'pix',
            'Customer' => [
                'name' => $data['customer_name'],
                'document' => $data['customer_document'],
                'emails' => [ $data['customer_email'] ],
                'phones' => [ $data['customer_phone'] ],
            ],
            'Split' => [
                'pix' => [
                    'type' => 'fixed',
                    'Companies' => [
                        [
                            'galaxPayId' => $data['split_galax_pay_id'],
                            'value' => $data['split_value'],
                        ]
                    ]
                ]
            ]
        ];

        try {
            $createPayment = Http::WithHeaders($headers)
                ->post(
                    env('CELCASH_BASE_URL') . '/v2/charges',
                    $body
                );

            $response = $createPayment->json();

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar gerar pagamento pix na celcash: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao gerar pagamento pix na celcash",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }
}
