<?php

namespace App\ExternalApis\CelCash;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CnpjUsersRequests
{
    static public function createUserCnpj($data)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $data['token']['token'],
            'Content-Type' => 'application/json'
        ];

        $body = [
            'name' => $data['name'],
            'document' => $data['document_cnpj'],
            'nameDisplay' => $data['name_display'],
            'phone' => $data['phone'],
            'emailContact' => $data['user']['email'],
            'responsibleDocument' => $data['document_cpf'],
            'typeCompany' => $data['type_company_cnpj'],
            'softDescriptor' => 'CompraFlamePay',
            'cnae' => $data['cnae'],
            'Address' => [
                'zipCode' => $data['address_zipcode'],
                'street' => $data['address_street'],
                'number' => $data['address_number'],
                'neighborhood' => $data['address_neighborhood'],
                'city' => $data['address_city'],
                'state' => $data['address_state'],
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
            Log::error('Erro ao tentar cadastrar usuário CNPJ na celcash: ' . $e->getMessage());

            return [
                'error' => [
                    'message' => "Erro ao cadastrar usuário cnpj na celcash",
                    'errorMessage' => $e->getMessage(),
                    'errorCode' => -1100
                ]
            ];
        }
    }

    static public function sendCnpjDocuments($token, $data)
    {
        $validator = Validator::make($data, [
            'monthly_income' => 'required|integer',
            'about' => 'required|string',
            'social_media_link' => 'required|string',
            'responsible_document_cpf' => 'required|string',
            'responsible_name' => 'required|string',
            'mother_name' => 'required|string',
            'birth_date' => 'required|string',
            'type_company_cnpj' => 'required|string',
            'last_contract' => 'required_if:type_company_cnpj,ltda,eireli,slu|string',
            'cnpj_card' => 'required_if:type_company_cnpj,mei,individualEntrepreneur|string',
            'rg_selfie' => 'required|string',
            'rg_front' => 'required|string',
            'rg_back' => 'required|string',
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

        if (isset($data['last_contract'])) {
            $docType = 'lastContract';
            $docValue = $data['last_contract'];
        }

        if (isset($data['cnpj_card'])) {
            $docType = 'cnpjCard';
            $docValue = $data['cnpj_card'];
        }

        $body = [
            'Fields' => [
                'monthlyIncome' => $data['monthly_income'],
                'about' => $data['about'],
                'socialMediaLink' => $data['social_media_link'],
            ],
            'Associate' => [
                [
                    'document' => $data['responsible_document_cpf'],
                    'name' => $data['responsible_name'],
                    'motherName' => $data['mother_name'],
                    'birthDate' => $data['birth_date'],
                    'type' => 'partner'
                ]
            ],
            'Documents' => [
                'Company' => [
                    $docType => $docValue
                ],
                'Personal' => [
                    'RG' => [
                        'selfie' => $data['rg_selfie'],
                        'front' => $data['rg_front'],
                        'back' => $data['rg_back'],
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
