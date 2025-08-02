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
use App\Models\SuperCredentials;

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

    static public function getSuperToken()
    {

        $superAuthToken = SuperCredentials::where('credential_type', 'bearer')
            ->where('updated_at', '>=', Carbon::now()->subMinutes(5))
            ->first();

        if (!$superAuthToken) {
            $superPublicToken = SuperCredentials::where('credential_type', 'public_token')->first();
            $superPrivateToken = SuperCredentials::where('credential_type', 'private_token')->first();

            $baseUrl = env('SUPER_BASE_URL');

            $headers = [
                'Content-Type' => 'application/json'
            ];

            $body = [
                'publicKey' => $superPublicToken->credential_value,
                'privateKey' => $superPrivateToken->credential_value,
            ];

            try {
                $createPayment = Http::WithHeaders($headers)
                    ->post(
                        $baseUrl . '/auth',
                        $body
                    );

                $response = $createPayment->json();

                $existsToken = SuperCredentials::where('credential_type', 'bearer')->first();

                if (!$existsToken) {
                    $createdToken = SuperCredentials::create([
                        'credential_type' => 'bearer',
                        'credential_value' => $response['data']['access_token'],
                    ]);
                } else {
                    $existsToken->update([
                        'credential_value' => $response['data']['access_token'],
                    ]);
                }

                return $response['data']['access_token'];
            }
            catch (\Exception $e) {
                Log::error('Erro ao tentar gerar token na adquirente: ' . $e->getMessage());

                return [
                    'error' => [
                        'message' => "Erro ao gerar token na adquirente",
                        'errorMessage' => $e->getMessage(),
                        'errorCode' => -1100
                    ]
                ];
            }
        } else {
            return $superAuthToken->credential_value;
        }
    }

    static public function getOwenToken()
    {
        $cert = env('OWEN_CERT');
        $key = env('OWEN_KEY');

        try {
            // Criar arquivos temporários para os certificados
            $tempKeyFile = tempnam(sys_get_temp_dir(), 'owen_key_');
            $tempCertFile = tempnam(sys_get_temp_dir(), 'owen_cert_');
            
            // Escrever os certificados nos arquivos temporários
            file_put_contents($tempKeyFile, $key);
            file_put_contents($tempCertFile, $cert);
            
            // Configuração do cURL para mTLS
            $ch = curl_init();
            
            // URL da API Owen - Endpoint de autenticação
            $apiUrl = env('OWEN_BASE_URL') . "/v1/auth/login";
            
            // Payload para autenticação
            $payload = [
                'email' => env('OWEN_CONTACT_M'),
                'password' => env('OWEN_S')
            ];
            
            // Configurações do cURL
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                // Configurações para mTLS
                CURLOPT_SSLCERT => $tempCertFile,     // Certificado do cliente
                CURLOPT_SSLKEY => $tempKeyFile,        // Chave privada do cliente
                CURLOPT_SSL_VERIFYPEER => false,       // Equivalente ao rejectUnauthorized: false
                CURLOPT_SSL_VERIFYHOST => false,       // Não verificar o hostname
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2, // Força TLS 1.2
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Limpar arquivos temporários
            unlink($tempKeyFile);
            unlink($tempCertFile);
            
            if ($error) {
                Log::error('Erro cURL na requisição Owen: ' . $error);
                return [
                    'error' => [
                        'message' => "Erro na conexão com a API Owen",
                        'errorMessage' => $error,
                        'errorCode' => -1100
                    ]
                ];
            }
            
            if ($httpCode !== 200) {
                Log::error('Erro HTTP na requisição Owen. Status: ' . $httpCode . ', Response: ' . $response);
                return [
                    'error' => [
                        'message' => "Erro na resposta da API Owen",
                        'errorMessage' => "HTTP Status: " . $httpCode . ", Response: " . $response,
                        'errorCode' => -1100
                    ]
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro ao decodificar JSON da resposta Owen: ' . json_last_error_msg());
                return [
                    'error' => [
                        'message' => "Erro ao processar resposta da API Owen",
                        'errorMessage' => json_last_error_msg(),
                        'errorCode' => -1100
                    ]
                ];
            }
            
            Log::info('Autenticação Owen realizada com sucesso');
            return $responseData['data']['idToken'];
            
        } catch (\Exception $e) {
            // Garantir que os arquivos temporários sejam removidos mesmo em caso de erro
            if (isset($tempKeyFile) && file_exists($tempKeyFile)) {
                unlink($tempKeyFile);
            }
            if (isset($tempCertFile) && file_exists($tempCertFile)) {
                unlink($tempCertFile);
            }
            
            Log::error('Erro ao tentar obter token Owen: ' . $e->getMessage());
            
            return [
                'error' => [
                    'message' => "Erro ao obter token Owen",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function generatePaymentPixSuper($data, $unicId)
    {
        $superAuthToken = PaymentsRequest::getSuperToken();

        if (isset($superAuthToken['error'])) {
            return [
                'error' => [
                    'message' => "Erro ao gerar pedido PIX na adquirente. Consultar tokens!",
                    'errorMessage' => $superAuthToken,
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $superAuthToken,
            'Content-Type' => 'application/json'
        ];

        $body = [
            "paymentType" => "pix",
            "billingData" => [
                "amount" => $data['price'],
                "postbackUrl" => env('SUPER_WEBHOOKS_BASE_URL'),
            ],
            "customerData" => [
                "firstName" => self::extractFirstName($data['customer']['name']) ?? 'Bulls',
                "lastName" => self::extractLastName($data['customer']['name']) ?? 'Pay',
                "email" => $data['customer']['email'] ?? 'compras@bullspay.com.br',
                "phone" => $data['customer']['phone'] ?? '21999999999',
                "document" => $data['customer']['document']['number'] ?? '39233341097',
            ],
        ];

        $baseUrl = env('SUPER_BASE_URL');

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

    static public function generatePaymentPixOwen($data, $unicId)
    {
        $owenAuthToken = PaymentsRequest::getOwenToken();

        if (isset($owenAuthToken['error'])) {
            return [
                'error' => [
                    'message' => "Erro ao gerar pedido PIX na adquirente. Consultar tokens!",
                    'errorMessage' => $owenAuthToken,
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $owenAuthToken,
            'Content-Type' => 'application/json'
        ];

        $body = [
            "transactionType" => "api",
            "externalReference" => $unicId,
            "postbackUrl" => env('OWEN_WEBHOOKS_BASE_URL'),
            "buyer" => [
                "id" => $unicId,
                "name" => $data['customer']['name'] ?? 'Bulls Pay',
                "email" => $data['customer']['email'] ?? 'compras@bullspay.com.br',
                "docNumber" => $data['customer']['document']['number'] ?? '39233341097',
                "phone" => $data['customer']['phone'] ?? '21999999999',
                "address" => [
                    "street" => "Rua dos Belos",
                    "number" => "123",
                    "city" => "São Paulo",
                    "state" => "SP",
                    "postalCode" => "04101300",
                    "country" => "BR",
                ]
            ],
            "product" => [
                [
                    "name" => "Produto Bulls Pay",
                    "description" => "Compra digital - Produto Bulls Pay",
                    "quantity" => 1,
                    "productPrice" => $data['price'],
                    "type" => "digital",
                ]
            ],
            "payment" => [
                "type" => "pix"
            ]
        ];

        $baseUrl = env('OWEN_API_BASE_URL');
        $cert = env('OWEN_CERT');
        $key = env('OWEN_KEY');

        try {
            // Criar arquivos temporários para os certificados
            $tempKeyFile = tempnam(sys_get_temp_dir(), 'owen_key_');
            $tempCertFile = tempnam(sys_get_temp_dir(), 'owen_cert_');
            
            // Escrever os certificados nos arquivos temporários
            file_put_contents($tempKeyFile, $key);
            file_put_contents($tempCertFile, $cert);
            
            // Configuração do cURL para mTLS
            $ch = curl_init();
            
            // URL da API Owen
            $apiUrl = $baseUrl . '/v1/transactions';
            
            // Configurações do cURL
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $owenAuthToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                // Configurações para mTLS
                CURLOPT_SSLCERT => $tempCertFile,     // Certificado do cliente
                CURLOPT_SSLKEY => $tempKeyFile,        // Chave privada do cliente
                CURLOPT_SSL_VERIFYPEER => false,       // Equivalente ao rejectUnauthorized: false
                CURLOPT_SSL_VERIFYHOST => false,       // Não verificar o hostname
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2, // Força TLS 1.2
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Limpar arquivos temporários
            unlink($tempKeyFile);
            unlink($tempCertFile);
            
            if ($error) {
                Log::error('Erro cURL na requisição Owen PIX: ' . $error);
                return [
                    'error' => [
                        'message' => "Erro na conexão com a API Owen",
                        'errorMessage' => $error,
                        'errorCode' => -1100
                    ]
                ];
            }
            
            if ($httpCode !== 200 && $httpCode !== 201) {
                Log::error('Erro HTTP na requisição Owen PIX. Status: ' . $httpCode . ', Response: ' . $response);
                return [
                    'error' => [
                        'message' => "Erro na resposta da API Owen",
                        'errorMessage' => "HTTP Status: " . $httpCode . ", Response: " . $response,
                        'errorCode' => -1100
                    ]
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro ao decodificar JSON da resposta Owen PIX: ' . json_last_error_msg());
                return [
                    'error' => [
                        'message' => "Erro ao processar resposta da API Owen",
                        'errorMessage' => json_last_error_msg(),
                        'errorCode' => -1100
                    ]
                ];
            }
            
            Log::info('Pagamento PIX Owen gerado com sucesso');
            return $responseData;
            
        } catch (\Exception $e) {
            // Garantir que os arquivos temporários sejam removidos mesmo em caso de erro
            if (isset($tempKeyFile) && file_exists($tempKeyFile)) {
                unlink($tempKeyFile);
            }
            if (isset($tempCertFile) && file_exists($tempCertFile)) {
                unlink($tempCertFile);
            }
            
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

    /**
     * Extrai o primeiro nome do nome completo
     * 
     * @param string $fullName
     * @return string
     */
    private static function extractFirstName($fullName)
    {
        $nameParts = explode(' ', trim($fullName));
        return $nameParts[0] ?? '';
    }

    /**
     * Extrai o sobrenome do nome completo (tudo exceto o primeiro nome)
     * 
     * @param string $fullName
     * @return string
     */
    private static function extractLastName($fullName)
    {
        $nameParts = explode(' ', trim($fullName));
        
        // Remove o primeiro nome
        array_shift($nameParts);
        
        // Retorna o resto como sobrenome
        return implode(' ', $nameParts);
    }
}
