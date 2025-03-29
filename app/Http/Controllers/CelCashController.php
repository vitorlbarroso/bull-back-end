<?php

namespace App\Http\Controllers;

use App\Events\PixelEvent;
use App\ExternalApis\CelCash\CnpjUsersRequests;
use App\ExternalApis\CelCash\CpfUsersRequests;
use App\Http\Helpers\Responses;
use App\Http\Requests\CelCashGateway\CreateUserCnpjRequest;
use App\Http\Requests\CelCashGateway\CreateUserCpfRequest;
use App\Http\Requests\CelCashGateway\GeneratePaymentPixRequest;
use App\Mail\Sales\GeneratePixMail;
use App\Models\CelcashCreateAccountsErrors;
use App\Models\CelcashPayments;
use App\Models\CelcashPaymentsGatewayData;
use App\Models\CelcashPaymentsOffers;
use App\Models\CelcashPaymentsPixData;
use App\Models\OfferPixel;
use App\Models\PendingPixelEvents;
use App\Models\ProductOffering;
use App\Models\User;
use App\Models\UserCelcashCnpjCredentials;
use App\Models\UserCelcashCnpjDocuments;
use App\Models\UserCelcashCpfCredentials;
use App\Models\UserCelcashCpfDocuments;
use App\Services\CelCashService;
use App\Services\PixelEventService;
use App\Services\UserService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        /* Salvando os dados do usuário na base de dados */
        try {
            $createUser = UserCelcashCpfCredentials::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'document' => $validatedData['document'],
                'phone' => $validatedData['phone'],
                'email' => $user->email,
                'soft_descriptor' => 'CompraBullsPay',
                'address_zipcode' => $validatedData['address_zipcode'],
                'address_street' => $validatedData['address_street'],
                'address_number' => $validatedData['address_number'],
                'address_neighborhood' => $validatedData['address_neighborhood'],
                'address_city' => $validatedData['address_city'],
                'address_state' => $validatedData['address_state'],
            ]);
        }
        catch(\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao salvar os dados de um usuário CPF no Banco de Dados |', [ 'ERRO' => $e->getMessage()]);

            CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao salvar dados de usuário cadastrado!',
                'request_infos' => json_encode($validatedData),
                'error' => $e->getMessage()
            ]);

            return Responses::ERROR('Ocorreu um erro imprevisto durante a criação da conta do usuário!', null, -1200, 400);
        }

        try {
            $createCpfDocuments = UserCelcashCpfDocuments::create([
                'user_cpf_credentials_id' => $createUser->id,
                'mother_name' => $validatedData['mother_name'],
                'birth_date' => $validatedData['birth_date'],
                'cnh' => 'not_send',
                'rg' => 'send',
                'rg_address_media' => $validatedData['rg_front'],
                'rg_front_media' => $validatedData['rg_front'],
                'rg_back_media' => $validatedData['rg_back'],
                'rg_selfie' => $validatedData['rg_selfie'],
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
                'soft_descriptor' => 'CompraBullsPay',
                'cnae' => $validatedData['cnae'],
                'type_company_cnpj' => $validatedData['type_company_cnpj'],
                'address_zipcode' => $validatedData['address_zipcode'],
                'address_street' => $validatedData['address_street'],
                'address_number' => $validatedData['address_number'],
                'address_neighborhood' => $validatedData['address_neighborhood'],
                'address_city' => $validatedData['address_city'],
                'address_state' => $validatedData['address_state'],
            ]);
        }
        catch(\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao salvar os dados de um usuário CNPJ criado no Banco de Dados |', [ 'ERRO' => $e->getMessage()]);

            CelcashCreateAccountsErrors::create([
                'title' => 'Erro ao salvar dados de usuário cadastrado!',
                'request_infos' => json_encode($validatedData),
                'error' => $e->getMessage()
            ]);

            return Responses::ERROR('Ocorreu um erro imprevisto durante a criação da conta do usuário!', null, -1200, 400);
        }

        $documentType = 'cnpj_card';
        $documentValue = 'cnpj_card';

        $documentsData = [
            'responsible_document_cpf' => $validatedData['responsible_document_cpf'],
            'responsible_name' => $validatedData['responsible_name'],
            'mother_name' => $validatedData['mother_name'],
            'birth_date' => $validatedData['birth_date'],
            'type_company_cnpj' => $validatedData['type_company_cnpj'],
            $documentType => $documentValue,
            'rg_address_media' => $validatedData['rg_selfie'],
            'rg_front_media' => $validatedData['rg_front'],
            'rg_back_media' => $validatedData['rg_back'],
            'company_document_media' => $validatedData['company_document'],
        ];

        try {
            $createCnpjDocuments = UserCelcashCnpjDocuments::create([
                'user_cnpj_credentials_id' => $createUser->id,
                'responsible_document_cpf' => $validatedData['responsible_document_cpf'],
                'responsible_name' => $validatedData['responsible_name'],
                'mother_name' => $validatedData['mother_name'],
                'birth_date' => $validatedData['birth_date'],
                'type' => 'partner',
                'company_document' => $validatedData['document_cnpj'],
                'cnh' => 'not_send',
                'rg' => 'send',
                'rg_address_media' => $validatedData['rg_selfie'],
                'rg_front_media' => $validatedData['rg_front'],
                'rg_back_media' => $validatedData['rg_back'],
                'company_document_media' => $validatedData['company_document'],
                'document_status' => 'analyzing'
            ]);

            User::where('id', $user->id)->update(['account_type' => 'PJ']);
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

        $data = [
            'customer' => [
                'name' => $validatedData['customer_name'],
                'email' => $validatedData['customer_email'],
                'document' => [
                    'number' => $validatedData['customer_document'],
                    'type' => 'cpf'
                ]
            ],
            'price' => $totalPrice
        ];

        if ($getPrincipalOffer->product->user->cash_in_adquirer_name == 'reflow' || $getPrincipalOffer->product->user->cash_in_adquirer_name == null) {
            $generatePayment = CelCashService::generatePaymentPix($data);
            $unicId = $generatePayment['orderId'];
            $pixReference = $generatePayment['pix']['payload'];

            $adquirerName = 'reflow';

            $returnData = [
                'galax_pay_id' => $generatePayment['orderId'],
                'qr_code' => $generatePayment['pix']['payload'],
                'upsell' => $getPrincipalOffer->sale_completed_page_url
            ];
        }

        if ($getPrincipalOffer->product->user->cash_in_adquirer_name == 'zendry') {
            $unicId = "BP_ID_" . Str::upper(Str::random(30));

            $generatePayment = CelCashService::generatePaymentPixByZendry($data, $unicId);

            if (isset($generatePayment['qrcode'])) {
                $unicId = $generatePayment['qrcode']['reference_code'];
                $pixReference = $generatePayment['qrcode']['content'];

                $adquirerName = 'zendry';

                $returnData = [
                    'galax_pay_id' => $generatePayment['qrcode']['reference_code'],
                    'qr_code' => $generatePayment['qrcode']['content'],
                    'upsell' => $getPrincipalOffer->sale_completed_page_url
                ];
            }
        }

        if (!empty($generatePayment['error'])) {
            $errorMessage = $generatePayment['error']['message'];

            return Responses::ERROR('Ocorreu um erro ao gerar o pedido!', $generatePayment, 1400, 400);
        }

        try {
            DB::transaction(function () use ($request, $getPrincipalOffer, $pixReference, $generatePayment, $totalPrice, $calculateTax, $formatedPayday, $validatedData, $offersData, $adquirerName, $unicId) {
                $createCelcashPayments = CelcashPayments::create([
                    'receiver_user_id' => $getPrincipalOffer->product->user->id,
                    'buyer_user_id' => null,
                    'galax_pay_id' => $unicId,
                    'type' => 'pix',
                    'installments' => 1,
                    'total_value' => $totalPrice,
                    'value_to_receiver' => $calculateTax,
                    'value_to_platform' => floor($totalPrice - $calculateTax),
                    'payday' => $formatedPayday,
                    'buyer_name' => $validatedData['customer_name'] ?? null,
                    'buyer_email' => $validatedData['customer_email'] ?? null,
                    'buyer_document_cpf' => $validatedData['customer_document'] ?? null,
                    'buyer_zipcode' => $validatedData['customer_zipcode'] ?? null,
                    'buyer_state' => $validatedData['customer_state'] ?? null,
                    'buyer_city' => $validatedData['customer_city'] ?? null,
                    'buyer_number' => $validatedData['customer_number'] ?? null,
                    'buyer_complement' => $validatedData['customer_complement'] ?? null,
                    'status' => 'pending_pix',
                    'adquirer' => $adquirerName,
                ]);

              $offerPixels = OfferPixel::where('product_offering_id', $getPrincipalOffer->id)
                    ->where('send_on_generate_payment', true)
                    ->get();
                Log::info("Validando os pixel para ser enviado agora ao gerar pagamento", ["pixel" => $offerPixels]);
                if($offerPixels->isEmpty()) { //valido se estiver vazio pois significa que nenhum pixel cadastro para a oferta é para disparar antes do pagamento
                    Log::info("Pixel a ser disparado na confirmacão do pagamento", ["pixel" => $validatedData['pixel_data']]);
                    if (isset($validatedData['pixel_data'])) {
                        PendingPixelEvents::create([
                            'offer_id' => $getPrincipalOffer->id,
                            'payment_id' => $createCelcashPayments->galax_pay_id,
                            'event_name' => 'Purchase',
                            'payload' => $validatedData['pixel_data'],
                            'status' => 'Waiting Payment'
                        ]); // salvo na tabela o evento do pixel para disparar após a confirmacao do pagamento
                    }
                }else{
                    $pixel_data=PixelEventService::FormatDataPixel($validatedData['pixel_data']);
                    Log::info("Colocando na fila o evento para disparar o pixel", ["pixel" => $pixel_data]);
                    event(new PixelEvent($getPrincipalOffer->id, 'Purchase', $pixel_data, $request->header('x-transaction-id')));
                }



                if ($adquirerName == 'reflow') {
                    $createPixDetails = CelcashPaymentsPixData::create([
                        'celcash_payments_id' => $createCelcashPayments->id,
                        'qr_code' => $generatePayment['pix']['encodedImage'],
                        'reference' => $generatePayment['pix']['payload'],
                    ]);
                }

                if ($adquirerName == 'zendry') {
                    $createPixDetails = CelcashPaymentsPixData::create([
                        'celcash_payments_id' => $createCelcashPayments->id,
                        'qr_code' => $pixReference,
                        'reference' => $pixReference,
                    ]);
                }

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

        try {
            Mail::to($validatedData['customer_email'])->send(new GeneratePixMail($validatedData['customer_name'], $pixReference, $getPrincipalOffer->product->email_support, ($totalPrice / 100), $unicId));
        }
        catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . '| Ocorreu um erro ao tentar enviar um e-mail de pagamento |', [ 'ERRO' => $e->getMessage()]);
        }

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
                    'celcash_payments.created_at',
                    'celcash_payments.status',
                    \DB::raw("(SELECT po.product_name
                   FROM celcash_payments_offers cpo
                   JOIN products_offerings pof ON cpo.products_offerings_id = pof.id
                   JOIN products po ON pof.product_id = po.id
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as product_title"),
                    \DB::raw("(SELECT cpo.type
                   FROM celcash_payments_offers cpo
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as buy_type")
                ])
                ->whereNotIn('celcash_payments.status', ['payed_pix', 'captured', 'chargeback', 'reversed', 'refunded'])
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
                    'celcash_payments.created_at',
                    'celcash_payments.status',
                    \DB::raw("(SELECT po.product_name
                   FROM celcash_payments_offers cpo
                   JOIN products_offerings pof ON cpo.products_offerings_id = pof.id
                   JOIN products po ON pof.product_id = po.id
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as product_title"),
                    \DB::raw("(SELECT cpo.type
                   FROM celcash_payments_offers cpo
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as buy_type")
                ])
                ->whereIn('celcash_payments.status', ['payed_pix', 'authorized'])
                ->orderBy('celcash_payments.id', 'DESC')
                ->paginate($itemsPerPage);

            return Responses::SUCCESS('', $getPaidPayments);
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar os pedidos!', $e->getMessage(), -1100, 400);
        }
    }

    public function chargebacks_payments(Request $request)
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
                    'celcash_payments.created_at',
                    'celcash_payments.status',
                    \DB::raw("(SELECT po.product_name
                   FROM celcash_payments_offers cpo
                   JOIN products_offerings pof ON cpo.products_offerings_id = pof.id
                   JOIN products po ON pof.product_id = po.id
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as product_title"),
                    \DB::raw("(SELECT cpo.type
                   FROM celcash_payments_offers cpo
                   WHERE cpo.celcash_payments_id = celcash_payments.id
                   LIMIT 1) as buy_type")
                ])
                ->whereIn('celcash_payments.status', ['chargeback', 'reversed'])
                ->orderBy('celcash_payments.id', 'DESC')
                ->paginate($itemsPerPage);

            return Responses::SUCCESS('', $getPaidPayments);
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar os pedidos!', $e->getMessage(), -1100, 400);
        }
    }
}
