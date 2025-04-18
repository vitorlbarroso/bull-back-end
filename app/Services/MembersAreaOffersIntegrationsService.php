<?php

namespace App\Services;
use App\Models\ProductOffering;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembersAreaOffersIntegrationsService
{
    public function addOffersAndModules(int $areaMembroId, int $oferta, array $modulos, $transactionId =null)
    {
        // Inicia a transação
        Log::info("|".$transactionId.'|Realizando o insert para realizar o relacionamento da area de membros com as ofertas e módulos informados',
            ['area de membros' => $areaMembroId,
                'Oferta' => $oferta, 'Módulos' => $modulos
            ]);
        DB::beginTransaction();
        try {
            // Verifica se já existe um registro correspondente
            $membersAreaOfferId = DB::table('members_area_offers')
                ->where('members_area_id', $areaMembroId)
                ->where('product_offering_id', $oferta)
                ->value('id');

            // Se não existir, insere e obtém o ID gerado
            if (!$membersAreaOfferId) {
                $membersAreaOfferId = DB::table('members_area_offers')->insertGetId([
                    'members_area_id' => $areaMembroId,
                    'product_offering_id' => $oferta
                ]);
            }

            $productOffering =ProductOffering::select('offer_name', 'description')
                ->where('id', $oferta)
                ->first();
            // Array para armazenar os módulos
            $modulesArray = [];

            // Percorrendo e inserindo os módulos na tabela offer_has_modules
            foreach ($modulos as $modulo) {
                DB::table('members_area_offer_has_modules')->updateOrInsert(
                    [
                        'modules_id' => $modulo['id'],
                        'members_area_offer_id' => $membersAreaOfferId // Agora temos certeza do ID
                    ],
                    [
                        'is_selected' => $modulo['is_selected']
                    ]
                );

                // Adicionando o módulo inserido ao array de resposta
                $modulesArray[] = [
                    'modules_id' => $modulo['id'],
                    'is_selected' => $modulo['is_selected']
                ];
            }

            DB::commit();
// Estrutura final do retorno
            return [
                'members_area_id' => $areaMembroId,
                'product_offering_id' => $oferta,
                'offer_name' => $productOffering->offer_name,
                'description'=> $productOffering->description,
                'modules' => $modulesArray,
            ];

        } catch (\Exception $e) {
            // Reverte a transação em caso de erro
            DB::rollBack();
            throw new \Exception("Erro ao adicionar ofertas e módulos na Integração: " . $e->getMessage());
        }
    }

    public function FormatShowData($result)
    {
        // Estrutura para armazenar as ofertas
        $offers = [];

        foreach ($result as $item) {
            // Verifica se a oferta já existe no array
            $offerKey = $item->product_offering_id;
            if (!isset($offers[$offerKey])) {
                $offers[$offerKey] = [
                    'offer_name' => $item->offer_name,
                    'description' => $item->description,
                    'product_offering_id' => $offerKey,
                    'modules' => [],
                ];
            }
            // Adiciona o módulo à oferta
            $offers[$offerKey]['modules'][] = [
                'module_name' => $item->module_name,
                'is_selected' => $item->is_selected,
                'module_id' => $item->module_id,
            ];
        }

// Reindexando o array para que fique sequencial
        return array_values($offers);

    }
    public function UpdateModulesToOfferIntegration($product_offering_id, $modulos, $TID = null)
    {
        Log::info("|".$TID.'|Atualizando integração da oferta e Módulos',
            ['Oferta' => $product_offering_id, 'Módulos' => $modulos ]);

        $modulesArray=[];
        DB::beginTransaction();
        // Recuperar o members_area_offer_id
        $membersAreaOffer = DB::table('members_area_offers')
            ->where('product_offering_id', $product_offering_id)
            ->where('members_area_id', $members_area_id)
            ->first();

        if (!$membersAreaOffer) {
            DB::rollBack();
            throw new ModelNotFoundException("Nenhuma oferta encontrada para product_offering_id: {$product_offering_id} e members_area_id: {$members_area_id}.", -1101);
        }

        $members_area_offer_id = $membersAreaOffer->id;
        foreach ($modulos as $module) {
            // Tenta encontrar o registro na tabela `offer_has_modules`
            $existingRecord = DB::table('members_area_offer_has_modules')
                ->where('members_area_offer_id', $members_area_offer_id)
                ->where('modules_id', $module['id'])
                ->first();

            if ($existingRecord) {
                // Se o registro existe, faz o update
                DB::table('members_area_offer_has_modules')
                    ->where('members_area_offer_id', $members_area_offer_id)
                    ->where('modules_id', $module['id'])
                    ->update([
                        'is_selected' => $module['is_selected'],
                        'updated_at' => now()
                    ]);
                $modulesArray[] = [
                    'modules_id' => $module['id'],
                    'is_selected' => $module['is_selected']
                ];

            } else {
                DB::rollBack();
                throw new ModelNotFoundException("A relação entre o módulo ID {$module['id']} e a oferta ID {$members_area_offer_id} não foi encontrada.", -1100);
            }
        }
        DB::commit();
        return [
            'product_offering_id' => $product_offering_id,
            'modules' => $modulesArray,
        ];
    }

    public function listOffers($membersAreaId, $TID)
    {
        Log::info("|".$TID.'|Realizando Listagem das ofertas disponíveis para integração ',
            ['Area de Membros selecionada ' => $membersAreaId ]);

       return DB::table('products_offerings as po')
            ->select('po.offer_name', 'po.id', 'p.product_name')
            ->whereNotExists(function ($query) use($membersAreaId) {
                $query->select(DB::raw(1))
                    ->from('members_area_offers as b')
                    ->whereRaw('po.id = b.product_offering_id')
                    ->where('b.members_area_id', $membersAreaId);
            })
            ->join('products as p', 'po.product_id', '=', 'p.id')
            ->where('p.user_id', Auth::id())
            ->where('p.is_deleted', false)
            ->where('p.is_blocked', false)
            ->get();
    }
}
