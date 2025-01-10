<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\MembersAreaOffersIntegrations\MembersAreaOffersIntegrationsRequest;
use App\Http\Requests\MembersAreaOffersIntegrations\MembersAreaOffersIntegrationsUpdateRequest;
use App\Http\Requests\MembersAreaOffersRequest;
use App\Models\MembersAreaOffers;
use App\Services\MembersAreaOffersIntegrationsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembersAreaOffersIntegrationsController extends Controller
{

    public function __construct(protected MembersAreaOffersIntegrationsService $memberAreaService)
    {
    }

    public function index()
    {
        return MembersAreaOffers::all();
    }

    public function store(MembersAreaOffersIntegrationsRequest $request)
    {
        $areaMembroId = $request->input('members_area_id');
        $oferta = $request->input('product_offering_id');
        $modulos = $request->input('modules');

        try {
            // Chama o Service para processar as inserções
            $response = $this->memberAreaService->addOffersAndModules($areaMembroId, $oferta, $modulos, $request->header('x-transaction-id'));
            return Responses::SUCCESS('Integração realizada com sucesso ', $response, 201);

        } catch (\Throwable $e) {
            Log::error("|".$request->header('x-transaction-id').'|Não foi possível criar a Integração dessa Area', ['error' => $e->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar a Integração', null, '-9999', 400);
        }
    }

    public function show($membersAreaOfferId)
    {
        $userId = auth()->id(); // Obtém o ID do usuário logado

        // Consulta para obter os dados necessários
        $result = DB::table('members_area_offers')
            ->join('products_offerings', 'members_area_offers.product_offering_id', '=', 'products_offerings.id')
            ->join('members_area', 'members_area.id', '=', 'members_area_offers.members_area_id')
            ->join('offer_has_modules', 'members_area_offers.product_offering_id', '=', 'offer_has_modules.product_offering_id')
            ->join('modules', 'offer_has_modules.modules_id', '=', 'modules.id')
            ->where('members_area.id', $membersAreaOfferId)
            ->where('members_area.user_id', $userId)
            ->where('members_area_offers.is_deleted', '=', FALSE)
            ->where('offer_has_modules.is_deleted', '=', FALSE)
            ->select(
                'products_offerings.offer_name',
                'products_offerings.id as product_offering_id',
                'products_offerings.description',
                'modules.module_name',
                'modules.id as module_id', // Adicionando o ID do módulo
                'offer_has_modules.is_selected'
            )
            ->get();

        $result = $this->memberAreaService->FormatShowData($result);
        return Responses::SUCCESS('Listagem das integrações realizada com sucesso ', $result, 201);
    }

    public function update(MembersAreaOffersIntegrationsUpdateRequest $request)
    {
        $ofertas = $request->input('product_offering_id');
        $modulos = $request->input('modules');

        try {
            // Chama o Service para processar as inserções
            $response = $this->memberAreaService->UpdateModulesToOfferIntegration( $ofertas, $modulos, $request->header('x-transaction-id'));
            return Responses::SUCCESS('Integração Atualizada com sucesso ', $response, 201);

        } catch (\Throwable $e) {
            Log::error("|".$request->header('x-transaction-id').'|Não foi possível Atualizar a Integração dessa Area', ['error' => $e->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar Atualizar a Integração', null, '-9999', 400);
        }
    }

    public function list_offers(Request $request, $membersAreaId)
    {
        try {
            $response = $this->memberAreaService->listOffers($membersAreaId, $request->header('x-transaction-id'));
            return Responses::SUCCESS('Listagem das ofertas diponíveis ', $response, 200);

        } catch (\Throwable $e) {
            Log::error("|".$request->header('x-transaction-id').'|Não foi possível listar as ofertas para essa integração', ['error' => $e->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar Listar as Ofertas', null, '-9999', 400);
        }
    }

    public function destroy(Request $request, $membership, $offerid)
    {
        Log::info('|' . $request->header('x-transaction-id') ."|Realizando remoção das integrações entre a area de membros ${membership} e a oferta ${offerid}");

        try {
            DB::beginTransaction();

            MembersAreaOffers::where('members_area_id', $membership)
                ->where('product_offering_id', $offerid)
                ->join('members_area', 'members_area.id', '=', 'members_area_offers.members_area_id')
                ->where('members_area.user_id', Auth::id())
                ->update(['members_area_offers.is_deleted' => true]);

            Log::info('|' . $request->header('x-transaction-id') ."|Remoção da integração da tabela members_area_offers realizada com sucesso, seguindo para remoção da tabela offer_has_modules");

            $moduleIds = DB::table('modules as m')
                ->join('members_area as ma', 'ma.id', '=', 'm.members_area_id')
                ->join('members_area_offers as mao', 'mao.members_area_id', '=', 'ma.id')
                ->where('ma.id', $membership)
                ->where('mao.product_offering_id', $offerid)
                ->pluck('m.id');// 2. Atualizar a tabela `offer_has_modules` onde `modules_id` está na lista de IDs obtidos
            Log::info('|' . $request->header('x-transaction-id') ."|Módulos obtidos dessa area de membros para serem removidos da integração ${moduleIds} e a oferta ${offerid}");

            DB::table('offer_has_modules')
                ->whereIn('modules_id', $moduleIds)
                ->where('product_offering_id', $offerid)
                ->update(['is_deleted' => true]);
            DB::commit();
            return Responses::SUCCESS('Remoção da Integração realizada com sucesso ', [$membership, $moduleIds], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("|".$request->header('x-transaction-id').'|Não foi possível Remover a Integração dessa Area', ['error' => $e->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar remover a Integração', null, '-9999', 400);
        }
    }
}
