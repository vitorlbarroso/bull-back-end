<?php

namespace App\ExternalApis\CelCash;

use App\Models\VenitCredentials;
use App\Models\ZendryTokens;
use App\Services\CelCashService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\RapdynTokens;

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
            'postbackUrl' => env('WEBHOOKS_BASE_URL')
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

    static public function generatePaymentPixZendry($data, $unicId)
    {

        $zendryAuthToken = PaymentsRequest::getZendryToken();

        if (isset($zendryAuthToken['error'])) {
            return [
                'error' => [
                    'message' => "Erro ao gerar pedido PIX na adquirente. Consultar tokens!",
                    'errorMessage' => $zendryAuthToken,
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $zendryAuthToken,
            'Content-Type' => 'application/json'
        ];

        $body = [
            'value_cents' => $data['price'],
            'external_reference' => $unicId,
        ];

        $baseUrl = env('ZENDRY_BASE_URL');

        try {
            $createPayment = Http::WithHeaders($headers)
                ->post(
                    $baseUrl . '/v1/pix/qrcodes',
                    $body
                );

            $response = $createPayment->json();

            if (isset($response['error'])) {
                return [
                    'error' => [
                        'message' => "Erro ao gerar pagamento pix na adquirente",
                        'errorMessage' => $response,
                        'errorCode' => 1300
                    ]
                ];
            }

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar gerar pagamento pix na adquirente: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao gerar pagamento pix na adquirente",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function generatePaymentPixRapdyn($data, $unicId)
    {

        $rapdynAuthToken = RapdynTokens::where('token_type', 'bearer')->first();

        if (!$rapdynAuthToken) {
            return [
                'error' => [
                    'message' => "Erro ao gerar pedido PIX na adquirente. Consultar tokens!",
                    'errorMessage' => $rapdynAuthToken,
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $rapdynAuthToken->token_value,
            'Content-Type' => 'application/json'
        ];

        $body = [
            "amount" => $data['price'],
            "method" => "pix",
            "customer" => [
                "name" => $data['customer']['name'],
                "email" => $data['customer']['email'],
                "phone" => "(84) 3848-6452",
                "document" => [
                    "value" => $data['customer']['document']['number'],
                    "type" => "CPF",
                ]
            ],
            "products" => [
                [
                    "name" => "Compra*BullsPay",
                    "quantity" => 1,
                    "price" => $data['price'],
                    "type" => "digital",
                ]
            ]
        ];

        $baseUrl = env('RAPDYN_BASE_URL');

        try {
            $createPayment = Http::WithHeaders($headers)
                ->post(
                    $baseUrl . '/payments',
                    $body
                );

            $response = $createPayment->json();

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar gerar pagamento pix na adquirente: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao gerar pagamento pix na adquirente",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function generatePaymentPixVenit($data, $unicId)
    {

        $credentials = VenitCredentials::first();

        if (!$credentials) {
            return [
                'error' => [
                    'message' => "Erro ao gerar pedido PIX na adquirente. Consultar tokens!",
                    'errorMessage' => 'err',
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Content-Type' => 'application/json',
            'x-public-key' => $credentials->public_key,
            'x-secret-key' => $credentials->secret_key,
        ];

        $body = [
            'amount' => $data['price'],
            'postbackUrl' => 'https://api.bullspay.com.br/api/webhooks/venit/transactions',
            "customer" => [
                "name" => $data['customer']['name'],
                "email" => $data['customer']['email'],
                "phone" => "13999999999",
                "document" => [
                    "type" => "cpf",
                    "number" => "00000000000"
                ]
            ],
            "traceable" => false,
            "items" => [
                [
                    "title" => "Produto",
                    "unitPrice" => $data['price'],
                    "quantity" => 1,
                    "tangible" => false,
                    "externalRef" => "",
                    "product_image" => ""
                ]
            ],
            "paymentMethod" => "pix",
            "installments" => "1"
        ];

        $baseUrl = env('VENIT_BASE_URL');

        try {
            $createPayment = Http::WithHeaders($headers)
                ->post(
                    'https://srv.venitpay.com.br/v1/transaction',
                    $body
                );

            $response = $createPayment->json();

            if (isset($response['error']) && $response['error'] != false) {
                return [
                    'error' => [
                        'message' => "Erro ao gerar pagamento pix na adquirente",
                        'errorMessage' => $response,
                        'errorCode' => 1300
                    ]
                ];
            }

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar gerar pagamento pix na adquirente: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao gerar pagamento pix na adquirente",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    public static function getZendryToken()
    {
        $getActiveToken = ZendryTokens::where('type', 'token')
            ->where('updated_at', '>=', Carbon::now()->subMinutes(10))
            ->first();

        if (!$getActiveToken) {
            $getPublicToken = ZendryTokens::where('type', 'public_token')->first();
            $getPrivateToken = ZendryTokens::where('type', 'private_token')->first();
            $publicToken = trim($getPublicToken->value);
            $privateToken = trim($getPrivateToken->value);

            $baseUrl = env('ZENDRY_BASE_URL');

            $authorizationString = "BASIC " . base64_encode("$publicToken:$privateToken");

            $headers = [
                'Authorization' => $authorizationString,
                'Content-Type' => 'application/json'
            ];

            $body = [
                'grant_type' => 'client_credentials',
            ];

            try {
                $createPayment = Http::WithHeaders($headers)
                    ->post(
                        $baseUrl . '/auth/generate_token',
                        $body
                    );

                $response = $createPayment->json();

                if (isset($response['error'])) {
                    return [
                        'error' => [
                            'message' => "Erro ao gerar credencial na adquirente",
                            'errorMessage' => $response,
                            'errorCode' => 1100
                        ]
                    ];
                }

                $hasToken = ZendryTokens::where('type', 'token')->first();

                if (!$hasToken) {
                    $createdToken = ZendryTokens::create([
                        'type' => 'token',
                        'value' => $response['access_token'],
                    ]);
                } else {
                    $hasToken->update([
                        'value' => $response['access_token'],
                    ]);
                }

                return $response['access_token'];
            }
            catch (\Exception $e) {
                Log::error('Erro ao tentar gerar credencial na zendry: ' . $e->getMessage());

                return [
                    'error' => [
                        'message' => "Erro ao gerar pagamento credencial na zendry",
                        'errorMessage' => $e->getMessage(),
                        'errorCode' => -1100
                    ]
                ];
            }
        }

        return $getActiveToken->value;
    }
}
