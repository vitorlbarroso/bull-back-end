<?php

namespace App\ExternalApis\CelCash;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CpfUsersRequests
{
    static public function createUserCpf($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $data['token']['token'],
            'Content-Type' => 'application/json'
        ];

        $body = [
            'name' => $data['name'],
            'document' => $data['document'],
            'phone' => $data['phone'],
            'emailContact' => $data['user']['email'],
            'softDescriptor' => 'CompraFlamePay',
            'Address' => [
                'zipCode' => $data['address_zipcode'],
                'street' => $data['address_street'],
                'number' => $data['address_number'],
                'neighborhood' => $data['address_neighborhood'],
                'city' => $data['address_city'],
                'state' => $data['address_state'],
            ],
            'Professional' => [
                'internalName' => 'others',
                'inscription' => $data['user']['id'] . "|" . $data['user']['email'] . "|CPF"
            ]
        ];

        try {
            $createSubAccountRequest = Http::WithHeaders($headers)
                ->post(
                    env('CELCASH_BASE_URL') . '/v2/company/subaccount',
                    $body
                );

            $response = $createSubAccountRequest->json();

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar cadastrar usuário CPF na celcash: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao cadastrar usuário cpf na celcash",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function sendCpfDocuments($token, $data)
    {
        $validator = Validator::make($data, [
            'mother_name' => 'required|string',
            'birth_date' => 'required|string',
            'monthly_income' => 'required|integer',
            'about' => 'required|string',
            'social_media_link' => 'required|string',
            'rg_selfie' => 'required|string',
            'rg_front' => 'required|string',
            'rg_back' => 'required|string',
            'rg_address' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Documentos obrigatórios não foram enviados! ' . $validator->errors(), );

            return [
                'error' => [
                    'message' => "Documentos obrigatórios não foram enviados!",
                    'errors' => $validator->errors(),
                    'errorCode' => 1100
                ]
            ];
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];

        $body = [
            'Fields' => [
                'motherName' => $data['mother_name'],
                'birthDate' => $data['birth_date'],
                'monthlyIncome' => $data['monthly_income'],
                'about' => $data['about'],
                'socialMediaLink' => $data['social_media_link'],
            ],
            'Documents' => [
                'Personal' => [
                    'RG' => [
                        'selfie' => $data['rg_selfie'],
                        'front' => $data['rg_front'],
                        'back' => $data['rg_back'],
                        'address' => $data['rg_address'],
                    ]
                ]
            ]
        ];

        try {
            $createSubAccountRequest = Http::WithHeaders($headers)
                ->post(
                    env('CELCASH_BASE_URL') . '/v2/company/mandatory-documents',
                    $body
                );

            $response = $createSubAccountRequest->json();

            return $response;
        }
        catch (\Exception $e) {
            Log::error('Erro ao tentar enviar documentos para a celcash: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao tentar enviar documentos para a celcash",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }
}
