<?php

namespace App\Http\Controllers;

use App\ExternalApis\CelCash\CnpjUsersRequests;
use App\ExternalApis\CelCash\CpfUsersRequests;
use App\Http\Helpers\Responses;
use App\Http\Requests\CelCashGateway\CreateUserCnpjRequest;
use App\Http\Requests\CelCashGateway\CreateUserCpfRequest;
use App\Http\Requests\CelCashGateway\GeneratePaymentPixRequest;
use App\Models\CelcashCreateAccountsErrors;
use App\Models\CelcashPayments;
use App\Models\CelcashPaymentsGatewayData;
use App\Models\CelcashPaymentsOffers;
use App\Models\CelcashPaymentsPixData;
use App\Models\ProductOffering;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCnpjDocuments;
use App\Models\UserCelcashCpfCredentials;
use App\Models\UserCelcashCpfDocuments;
use App\Services\CelCashService;
use App\Services\UserService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\Return_;

class CelCashController extends Controller
{
    public function create_user_cpf(CreateUserCpfRequest $request)
    {
        /* Validando os dados da requisição */
        $validatedData = $request->validated();

        $user = Auth::user();

        /* Verificando se o usuário já possui cadastro CPF */
        try {
            $verifyIfUserHasRegister = UserCelcashCpfCredentials::where('user_id', $user->id)->exists();

            if ($verifyIfUserHasRegister) {
                Log::error("|" . request()->header('x-transaction-id') . '| O usuário ' . $user->id . ' já possui um cadastro CPF nessa conta!');

                return Responses::ERROR('O usuário já possui um cadastro CPF nessa conta!', null, 1100, 400);
            }
        }
        catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao tentar verificar se o usuário já possui um cadastro CPF nessa conta |', [ 'ERRO' => $e->getMessage()]);

            return Responses::ERROR('Ocorreu um erro ao verificar se o usuário já possui um cadastro CPF!', null, -1100, 400);
        }

        /* Requisição para criar usuário */
        $createUserCpf = CelCashService::createUserCpf($validatedData);

        /* Validando erros */
        if (!empty($createUserCpf['error'])) {
            $errorMessage = $createUserCpf['error']['message'];

            $createUserCpfErrorLog = CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao criar usuário!',
                'request_infos' => json_encode($validatedData),
                'error' => $errorMessage
            ]);

            if ($errorMessage == 'Itens insuficientes') {
                return Responses::ERROR('Campos obrigatórios para criação do usuário CPF não foram localizados!', $createUserCpf['error']['errors'], 1100, 400);
            }

            if (
                $errorMessage == 'Token não localizado ou expirado' ||
                $errorMessage == 'Erro ao buscar token celcash' ||
                $errorMessage == 'Access token inválido.'
            ) {
                $generateToken = CelCashService::generateToken();

                $createUserCpf = CelCashService::createUserCpf($validatedData);

                if (!empty($createUserCpf['error'])) {
                    return Responses::ERROR('Ocorreu um erro interno relacionado a tokens!', $errorMessage, 1200, 400);
                }
            }

            if ($errorMessage == 'Erro ao cadastrar usuário cpf na celcash') {
                return Responses::ERROR('Ocorreu um erro interno na criação da conta!', $errorMessage, 1300, 400);
            }

            /* Validando erro de documento já sendo utilizado */
            if ($errorMessage == 'O documento informado já está sendo utilizado por outra empresa.') {

                /*
                 *
                 * ESCREVER A LÓGICA PARA QUANDO O DOCUMENTO JÁ ESTIVER SENDO UTILIZADO
                 *
                 * */

                return Responses::ERROR('Ocorreu um erro interno na criação do usuário!', $errorMessage, 1400, 400);
            }

            return Responses::ERROR('Ocorreu um erro inesperado durante o fluxo de criação!', $errorMessage, 1500, 400);
        }

        /* Salvando os dados do usuário na base de dados */
        try {
            $createUser = UserCelcashCpfCredentials::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'document' => $validatedData['document'],
                'phone' => $validatedData['phone'],
                'email' => $user->email,
                'soft_descriptor' => 'CompraFlamePay',
                'address_zipcode' => $validatedData['address_zipcode'],
                'address_street' => $validatedData['address_street'],
                'address_number' => $validatedData['address_number'],
                'address_neighborhood' => $validatedData['address_neighborhood'],
                'address_city' => $validatedData['address_city'],
                'address_state' => $validatedData['address_state'],
                'galax_pay_id' => $createUserCpf['Company']['galaxPayId'],
                'api_auth_galax_id' => $createUserCpf['Company']['ApiAuth']['galaxId'],
                'api_auth_galax_hash' => $createUserCpf['Company']['ApiAuth']['galaxHash'],
                'api_auth_public_token' => $createUserCpf['Company']['ApiAuth']['publicToken'],
                'api_auth_confirm_hash_webhook' => $createUserCpf['Company']['ApiAuth']['confirmHashWebhook'],
            ]);
        }
        catch(\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao salvar os dados de um usuário CPF criado na CELCASH no Banco de Dados |', [ 'ERRO' => $e->getMessage()]);

            CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao salvar dados de usuário cadastrado na celcash!',
                'request_infos' => json_encode($validatedData),
                'error' => $e->getMessage()
            ]);

            return Responses::ERROR('Ocorreu um erro imprevisto durante a criação da conta do usuário!', null, -1200, 400);
        }

        /* Enviar documentos */
        $getToken = CelCashService::getSubaccountToken($createUserCpf['Company']['ApiAuth']['galaxId'], $createUserCpf['Company']['ApiAuth']['galaxHash']);

        if (!empty($getToken['error'])) {
            return Responses::ERROR('Usuário cadastrado. Documentos não foram enviados para análise!', $getToken, 1600, 200);
        }

        sleep(10);

        $sendDocuments = CpfUsersRequests::sendCpfDocuments($getToken['access_token'], [
            'mother_name' => $validatedData['mother_name'],
            'birth_date' => $validatedData['birth_date'],
            'monthly_income' => $validatedData['monthly_income'],
            'about' => $validatedData['about'],
            'social_media_link' => $validatedData['social_media_link'],
            'rg_selfie' => $validatedData['rg_selfie'],
            'rg_front' => $validatedData['rg_front'],
            'rg_back' => $validatedData['rg_back'],
            'rg_address' => $validatedData['rg_address'],
        ]);

        if (!empty($sendDocuments['error'])) {
            Log::error('Ocorreu um erro ao enviar os documentos: ', ['error' => $sendDocuments]);

            return Responses::ERROR('Usuário cadastrado. Documentos não foram enviados para análise!', $sendDocuments, 1700, 200);
        }

        try {
            $createCpfDocuments = UserCelcashCpfDocuments::create([
                'user_cpf_credentials_id' => $createUser->id,
                'mother_name' => $validatedData['mother_name'],
                'birth_date' => $validatedData['birth_date'],
                'monthly_income' => $validatedData['monthly_income'],
                'about' => $validatedData['about'],
                'social_media_link' => $validatedData['social_media_link'],
                'cnh' => 'not_send',
                'rg' => 'send',
                'rg_address' => 'Enviado',
                'rg_front' => 'Enviado',
                'rg_back' => 'Enviado',
                'rg_selfie' => 'Enviado',
                'document_status' => 'analyzing'
            ]);
        }
        catch(\Exception $e) {
            Log::error('Ocorreu um erro ao tentar salvar os documentos de um usuário na database: ' . $e->getMessage());

            return Responses::ERROR('Usuário cadastrado e enviado. Ocorreu um erro ao salvar os dados do usuário!', $e->getMessage(), -1300, 400);
        }

        return Responses::SUCCESS('Usuário cadastrado e inserido na fila de análises!', null, 201);
    }

    public function create_user_cnpj(CreateUserCnpjRequest $request)
    {
        /* Validando os dados da requisição */
        $validatedData = $request->validated();

        $user = Auth::user();

        /* Verificando se o usuário já possui cadastro CPF */
        try {
            $verifyIfUserHasRegister = UserCelcashCnpjCredentials::where('user_id', $user->id)->exists();

            if ($verifyIfUserHasRegister) {
                Log::error("|" . request()->header('x-transaction-id') . '| O usuário ' . $user->id . ' já possui um cadastro CNPJ nessa conta!');

                return Responses::ERROR('O usuário já possui um cadastro CNPJ nessa conta!', null, 1100, 400);
            }
        }
        catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao tentar verificar se o usuário já possui um cadastro CNPJ nessa conta |', [ 'ERRO' => $e->getMessage()]);

            return Responses::ERROR('Ocorreu um erro ao verificar se o usuário já possui um cadastro CNPJ!', null, -1100, 400);
        }

        /* Requisição para criar usuário */
        $createUserCnpj = CelCashService::createUserCnpj($validatedData);

        /* Validando erros */
        if (!empty($createUserCnpj['error'])) {
            $errorMessage = $createUserCnpj['error']['message'];

            $createUserCnpjErrorLog = CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao criar usuário!',
                'request_infos' => json_encode($validatedData),
                'error' => $errorMessage
            ]);

            if ($errorMessage == 'Itens insuficientes') {
                return Responses::ERROR('Campos obrigatórios para criação do usuário CNPJ não foram localizados!', $createUserCnpj['error']['errors'], 1100, 400);
            }

            if (
                $errorMessage == 'Token não localizado ou expirado' ||
                $errorMessage == 'Erro ao buscar token celcash' ||
                $errorMessage == 'Access token inválido.'
            ) {
                $generateToken = CelCashService::generateToken();

                $createUserCpf = CelCashService::createUserCpf($validatedData);

                if (!empty($createUserCpf['error'])) {
                    return Responses::ERROR('Ocorreu um erro interno relacionado a tokens!', $errorMessage, 1200, 400);
                }
            }

            if ($errorMessage == 'Erro ao cadastrar usuário cnpj na celcash') {
                return Responses::ERROR('Ocorreu um erro interno na criação da conta!', $errorMessage, 1300, 400);
            }

            /* Validando erro de documento já sendo utilizado */
            if ($errorMessage == 'O documento informado já está sendo utilizado por outra empresa.') {

                /*
                 *
                 * ESCREVER A LÓGICA PARA QUANDO O DOCUMENTO JÁ ESTIVER SENDO UTILIZADO
                 *
                 * */

                return Responses::ERROR('Ocorreu um erro interno na criação do usuário!', $errorMessage, 1400, 400);
            }

            return Responses::ERROR('Ocorreu um erro inesperado durante o fluxo de criação!', $errorMessage, 1500, 400);
        }

        /* Salvando os dados do usuário na base de dados */
        try {
            $createUser = UserCelcashCnpjCredentials::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'document_cpf' => $validatedData['document_cpf'],
                'document_cnpj' => $validatedData['document_cnpj'],
                'name_display' => $validatedData['name_display'],
                'phone' => $validatedData['phone'],
                'email' => $user->email,
                'soft_descriptor' => 'CompraFlamePay',
                'cnae' => $validatedData['cnae'],
                'type_company_cnpj' => $validatedData['type_company_cnpj'],
                'address_zipcode' => $validatedData['address_zipcode'],
                'address_street' => $validatedData['address_street'],
                'address_number' => $validatedData['address_number'],
                'address_neighborhood' => $validatedData['address_neighborhood'],
                'address_city' => $validatedData['address_city'],
                'address_state' => $validatedData['address_state'],
                'galax_pay_id' => $createUserCnpj['Company']['galaxPayId'],
                'api_auth_galax_id' => $createUserCnpj['Company']['ApiAuth']['galaxId'],
                'api_auth_galax_hash' => $createUserCnpj['Company']['ApiAuth']['galaxHash'],
                'api_auth_public_token' => $createUserCnpj['Company']['ApiAuth']['publicToken'],
                'api_auth_confirm_hash_webhook' => $createUserCnpj['Company']['ApiAuth']['confirmHashWebhook'],
            ]);
        }
        catch(\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao salvar os dados de um usuário CNPJ criado na CELCASH no Banco de Dados |', [ 'ERRO' => $e->getMessage()]);

            CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao salvar dados de usuário cadastrado na celcash!',
                'request_infos' => json_encode($validatedData),
                'error' => $e->getMessage()
            ]);

            return Responses::ERROR('Ocorreu um erro imprevisto durante a criação da conta do usuário!', null, -1200, 400);
        }

        /* Enviar documentos */
        $getToken = CelCashService::getSubaccountToken($createUserCnpj['Company']['ApiAuth']['galaxId'], $createUserCnpj['Company']['ApiAuth']['galaxHash']);

        if (!empty($getToken['error'])) {
            return Responses::ERROR('Usuário cadastrado. Documentos não foram enviados para análise!', $getToken, 1600, 200);
        }

        sleep(10);

        $documentType = $validatedData['type_company_cnpj'] == 'eireli' || $validatedData['type_company_cnpj'] == 'ltda' || $validatedData['type_company_cnpj'] == 'slu' ? 'last_contract' : 'cnpj_card';
        $documentValue = $validatedData['type_company_cnpj'] == 'eireli' || $validatedData['type_company_cnpj'] == 'ltda' || $validatedData['type_company_cnpj'] == 'slu' ? $validatedData['last_contract'] : $validatedData['cnpj_card'];

        $documentsData = [
            'monthly_income' => $validatedData['monthly_income'],
            'about' => $validatedData['about'],
            'social_media_link' => $validatedData['social_media_link'],
            'responsible_document_cpf' => $validatedData['responsible_document_cpf'],
            'responsible_name' => $validatedData['responsible_name'],
            'mother_name' => $validatedData['mother_name'],
            'birth_date' => $validatedData['birth_date'],
            'type_company_cnpj' => $validatedData['type_company_cnpj'],
            $documentType => $documentValue,
            'rg_selfie' => $validatedData['rg_selfie'],
            'rg_front' => $validatedData['rg_front'],
            'rg_back' => $validatedData['rg_back'],
        ];

        $sendDocuments = CnpjUsersRequests::sendCnpjDocuments($getToken['access_token'], $documentsData);

        if (!empty($sendDocuments['error'])) {
            Log::error('Ocorreu um erro ao enviar os documentos: ', ['error' => $sendDocuments]);

            return Responses::ERROR('Usuário cadastrado. Documentos não foram enviados para análise!', $sendDocuments, 1700, 200);
        }

        try {
            $createCnpjDocuments = UserCelcashCnpjDocuments::create([
                'user_cnpj_credentials_id' => $createUser->id,
                'monthly_income' => $validatedData['monthly_income'],
                'about' => $validatedData['about'],
                'social_media_link' => $validatedData['social_media_link'],
                'responsible_document_cpf' => $validatedData['responsible_document_cpf'],
                'responsible_name' => $validatedData['responsible_name'],
                'mother_name' => $validatedData['mother_name'],
                'birth_date' => $validatedData['birth_date'],
                'type' => 'partner',
                'company_document' => $validatedData['document_cnpj'],
                'cnh' => 'not_send',
                'rg' => 'send',
                'rg_front' => 'Enviado',
                'rg_back' => 'Enviado',
                'rg_selfie' => 'Enviado',
                'document_status' => 'analyzing'
            ]);
        }
        catch(\Exception $e) {
            Log::error('Ocorreu um erro ao tentar salvar os documentos de um usuário na database: ' . $e->getMessage());

            return Responses::ERROR('Usuário cadastrado e enviado. Ocorreu um erro ao salvar os dados do usuário!', $e->getMessage(), -1300, 400);
        }

        return Responses::SUCCESS('Usuário cadastrado e inserido na fila de análises!', null, 201);
    }

    public function generate_payment_pix(GeneratePaymentPixRequest $request)
    {
        $validatedData = $request->validated();

        $getPrincipalOffer = ProductOffering::where('id', $validatedData['principal_offer'])
            ->where('is_deleted', 0)
            ->with('product.user')
            ->first();

        $getUserPayPendences = UserService::getPayConfigsPendences($getPrincipalOffer->product->user);

        if ($getUserPayPendences || $getPrincipalOffer->product->user->is_blocked) {
            return Responses::ERROR('O usuário não está permitido a receber pagamentos!', null, 1400, 400);
        }

        if (!$getPrincipalOffer) {
            return Responses::ERROR('Oferta principal não localizada!', null, 1100, 400);
        }

        if (!$getPrincipalOffer->enable_pix) {
            return Responses::ERROR('A oferta principal não esta configurada para receber pagamentos PIX!', null, 1200, 400);
        }

        if ($getPrincipalOffer->product->is_blocked || $getPrincipalOffer->product->is_deleted || !$getPrincipalOffer->product->is_active) {
            return Responses::ERROR('Oferta principal não está disponível para novas vendas!', null, 1300, 400);
        }

        $offersData = [];

        $offersData[] = [
            "id" => $getPrincipalOffer->id,
            "price" => round($getPrincipalOffer->price * 100),
            "type" => 'principal'
        ];

        if ($validatedData['orderbumps']) {
            foreach ($validatedData['orderbumps'] as $orderbump) {
                $getOrderbumpOffer = ProductOffering::where('id', $orderbump['id'])
                    ->where('is_deleted', 0)
                    ->with('product.user')
                    ->first();

                if (
                    $getOrderbumpOffer &&
                    !$getOrderbumpOffer->product->is_blocked &&
                    !$getOrderbumpOffer->product->is_deleted &&
                    $getOrderbumpOffer->product->is_active &&
                    $getOrderbumpOffer->product->user->id == $getPrincipalOffer->product->user->id
                ) {
                    $offersData[] = [
                        "id" => $getOrderbumpOffer->id,
                        "price" => round($getOrderbumpOffer->price * 100),
                        "type" => 'orderbump'
                    ];
                }
            }
        }

        $totalPrice = array_sum(array_column($offersData, 'price'));

        $calculateTax = CelCashService::calculateTax('pix', $getPrincipalOffer->product->user->id, $totalPrice);

        $generatePayday = new DateTime();
        $generatePayday->modify('+2 day');
        $formatedPayday = $generatePayday->format('Y-m-d');

        if ($getPrincipalOffer->product->user->account_type->value == 'PF') {
            $getGalaxPayId = UserCelcashCpfCredentials::where('user_id', $getPrincipalOffer->product->user->id)
                ->select(['galax_pay_id'])
                ->first();
        } else {
            $getGalaxPayId = UserCelcashCnpjCredentials::where('user_id', $getPrincipalOffer->product->user->id)
                ->select(['galax_pay_id'])
                ->first();
        }

        $data = [
            'calendario' => [
                'expiracao' => 86400
            ],
            'valor' => [
                'original' => ($totalPrice / 100),
                'modalidadeAlteracao' => 0
            ],
        ];

        $generatePayment = CelCashService::generatePaymentPix($data);

        if (!empty($generatePayment['error'])) {
            $generateToken = CelCashService::generateToken();

            $generatePayment = CelCashService::generatePaymentPix($data);

            if (!empty($generatePayment['error'])){
                $errorMessage = $generatePayment['error']['message'];

                return Responses::ERROR('Ocorreu um erro ao gerar o pedido!', $generatePayment, 1400, 400);
            }
        }

        try {
            DB::transaction(function () use ($getPrincipalOffer, $generatePayment, $totalPrice, $calculateTax, $formatedPayday, $validatedData, $offersData) {
                $createCelcashPayments = CelcashPayments::create([
                    'receiver_user_id' => $getPrincipalOffer->product->user->id,
                    'buyer_user_id' => null,
                    'galax_pay_id' => $generatePayment['txid'],
                    'type' => 'pix',
                    'installments' => 1,
                    'total_value' => $totalPrice,
                    'value_to_receiver' => $calculateTax,
                    'value_to_platform' => floor($totalPrice - $calculateTax),
                    'payday' => $formatedPayday,
                    'buyer_name' => $validatedData['customer_name'] ?? null,
                    'buyer_email' => $validatedData['customer_email'] ?? null,
                    'buyer_document_cpf' => $validatedData['customer_document'] ?? null,
                    'status' => 'pending_pix'
                ]);

                $createPixDetails = CelcashPaymentsPixData::create([
                    'celcash_payments_id' => $createCelcashPayments->id,
                    'qr_code' => $generatePayment['pixCopiaECola'],
                    'reference' => $generatePayment['pixCopiaECola'],
                ]);

                foreach ($offersData as $offer) {
                    CelcashPaymentsOffers::create([
                        'celcash_payments_id' => $createCelcashPayments->id,
                        'products_offerings_id' => $offer['id'],
                        'type' => $offer['type']
                    ]);
                }
            });
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao salvar o pedido!', $e->getMessage(), 1500, 400);
        }

        $returnData = [
            'galax_pay_id' => $generatePayment['txid'],
            'qr_code' => $generatePayment['pixCopiaECola'],
            'upsell' => $getPrincipalOffer->sale_completed_page_url
        ];

        return Responses::SUCCESS('Pedido pix gerado com sucesso!', $returnData, 200);
    }

    public function unpaid_payments(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        $user = Auth::user();

        try {
            $getPendingPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->select([
                    'celcash_payments.id',
                    'celcash_payments.receiver_user_id',
                    'celcash_payments.type',
                    'celcash_payments.total_value',
                    'celcash_payments.payday',
                    'celcash_payments.buyer_name',
                    'celcash_payments.buyer_email',
                    'celcash_payments.buyer_document_cpf',
                    'celcash_payments.status',
                    'products.product_name as product_title',
                    'celcash_payments_offers.type as buy_type'
                ])
                ->leftJoin('celcash_payments_offers', 'celcash_payments.id', '=', 'celcash_payments_offers.celcash_payments_id')
                ->leftJoin('products_offerings', 'celcash_payments_offers.products_offerings_id', '=', 'products_offerings.id')
                ->leftJoin('products', 'products_offerings.product_id', '=', 'products.id')
                ->where('celcash_payments.status', '!=', 'payed_pix')
                ->where('celcash_payments.status', '!=', 'captured')
                ->orderBy('celcash_payments.id', 'DESC')
                ->paginate($itemsPerPage);

            return $getPendingPayments;
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar os pedidos!', $e->getMessage(), -1100, 400);
        }
    }

    public function all_payments(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        $user = Auth::user();

        try {
            $getAllPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->with('payment_offers', function ($query) {
                    $query->with('offer', function ($query) {
                        $query->with('product', function ($query) {
                            $query->select('id', 'product_name');
                        })
                            ->select('id', 'product_id');
                    })
                        ->select('id', 'celcash_payments_id', 'products_offerings_id');
                })
                ->select(['id', 'receiver_user_id', 'type', 'total_value', 'payday', 'buyer_name', 'buyer_email', 'buyer_document_cpf', 'status'])
                ->orderBy('id', 'DESC')
                ->paginate($itemsPerPage);

            return Responses::SUCCESS('', $getAllPayments);
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar os pedidos!', $e->getMessage(), -1100, 400);
        }
    }

    public function paid_payments(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        $user = Auth::user();

        try {
            $getPaidPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->select([
                    'celcash_payments.id',
                    'celcash_payments.receiver_user_id',
                    'celcash_payments.type',
                    'celcash_payments.total_value',
                    'celcash_payments.payday',
                    'celcash_payments.buyer_name',
                    'celcash_payments.buyer_email',
                    'celcash_payments.buyer_document_cpf',
                    'celcash_payments.status',
                    'products.product_name as product_title',
                    'celcash_payments_offers.type as buy_type'
                ])
                ->leftJoin('celcash_payments_offers', 'celcash_payments.id', '=', 'celcash_payments_offers.celcash_payments_id')
                ->leftJoin('products_offerings', 'celcash_payments_offers.products_offerings_id', '=', 'products_offerings.id')
                ->leftJoin('products', 'products_offerings.product_id', '=', 'products.id')
                ->where(function ($query) {
                    $query->where('celcash_payments.status', 'payed_pix')
                        ->orWhere('celcash_payments.status', 'authorized');
                })
                ->orderBy('celcash_payments.id', 'DESC')
                ->paginate($itemsPerPage);

            return Responses::SUCCESS('', $getPaidPayments);
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar os pedidos!', $e->getMessage(), -1100, 400);
        }
    }
}
