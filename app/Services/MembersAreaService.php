<?php

namespace App\Services;

use App\Http\Helpers\Responses;
use App\Models\MembersArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class MembersAreaService
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }
    public function Update($area_to_update, $new_data_area, $transactionid = null)
    {
        Log::info("|" . $transactionid. "| Método Update de MembersAreaService |",
            ['Area de Membros Atual' => $area_to_update, 'Dados Nova Area' => $new_data_area]);
        $new_data_area['id'] = $area_to_update->id;

        return DB::transaction(function () use ($area_to_update, $new_data_area, $transactionid) {
            // atualizar na media o members_area_id para null onde o members_area_id seja o id da area a ser atualizada
            $this->mediaService->RemoveOldMedia((object) $new_data_area, $transactionid, 'members_area_id');
            $mediaUpdated = $this->updateMediaToMembership( (object) $new_data_area, $new_data_area['id'], $transactionid);
            //removo os atribudos de media pois não fazem parte do modelo da area de membros
            $new_data_area = collect($new_data_area)->except(['media_id_logo', 'media_id_thumb'])->toArray();
            $area_to_update->update($new_data_area);
            $updated_members_area = $area_to_update->fresh();
            $updated_members_area->media = $mediaUpdated;
            return $this->formatAreaData($updated_members_area);;
        });
    }

    public function show($itemsPerPage)
    {
        return MembersArea::select('id', 'area_name', 'area_type','slug', 'is_active', 'is_blocked')
            ->where('user_id', Auth::user()->id)
            ->with(['media:id,members_area_id,s3_url,media_type'])
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->paginate($itemsPerPage);
    }
    public function showByMembership($itemsPerPage, $membersAreaId = null)
    {
        // Busca e paginação de memberships delegada ao serviço
        $memberships = $this->getMemberships(Auth::id(), $itemsPerPage, $membersAreaId);
        // Formatação dos dados
        $formattedData = $this->formatMemberships($memberships);
        // Retorna os dados paginados formatados
        return $this->paginateFormattedData($formattedData, $memberships);
    }

    private function formatMemberships($memberships)
    {
        return $memberships->map(function ($membership) {
            return [
                'members_area_name' => $membership->area_name,
                'area_type' => $membership->area_type,
                'id' => $membership->id,
                'modules' => $this->formatModules($membership->modules),
            ];
        });
    }

    private function formatModules($modules)
    {
        return $modules->map(function ($module) {
            return [
                'id' => $module->id,
                'module_name' => $module->module_name,
                'is_active' => $module->is_active,
                'is_blocked' => $module->is_blocked,
                'media' => $this->formatMedia($module->media),
            ];
        });
    }

    private function formatMedia($media)
    {
        return $media->map(function ($mediaItem) {
            return [
                's3_url' => $mediaItem->s3_url,
                'media_type' => $mediaItem->media_type,
            ];
        });
    }

    private function paginateFormattedData($formattedData, $originalPagination)
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedData, // Dados formatados
            $originalPagination->total(), // Total de itens
            $originalPagination->perPage(), // Itens por página
            $originalPagination->currentPage(), // Página atual
            ['path' => request()->url(), 'query' => request()->query()] // Parâmetros de paginação
        );
    }
    public function getMedia($area)
    {
        return MembersArea::with('media:members_area_id,s3_url,media_type')
            ->find($area->id);
    }

    public function formatAreaData($area) {
        return [
            'id' => $area->id,
            'area_name' => $area->area_name,
            'area_type' => $area->area_type,
            'is_active' => $area->is_active,
            'is_blocked' => $area->is_blocked,
            'comments_allow' => $area->comments_allow,
            'is_comments_auto_approve' => $area->is_comments_auto_approve,
            'layout_type' => $area->layout_type,
            'slug' => $area->slug,
            'media' => $area->media->map(function($media){
                return [
                    's3_url' => $media->s3_url,
                    'media_type' => $media->media_type,
                ];
            }, is_array($area->media) ? $area->media : [])
        ];
    }

    public function createMembersArea($members, $request)
    {
        return DB::transaction(function () use ($members, $request) {
            // Criar a área de membros
            $create_members_area = MembersArea::create($members);
            // Atualizar as mídias relacionadas (logo e thumbnail)
            $mediaUpdated = $this->updateMediaToMembership($request, $create_members_area->id, request()->header('x-transaction-id'));
            // Associar mídias à área de membros
            $create_members_area->media = $mediaUpdated;
            return $this->formatAreaData($create_members_area);;
        });
    }

    private function updateMediaToMembership($request, $membersAreaId, $transactionid = null)
    {
        $mediaUpdated = collect();
        Log::info("|" . $transactionid. "| Método updateMediaToMembership de MembersAreaService |",
            ['Request ' => $request, 'Id da área' => $membersAreaId]);

        if (isset($request->media_id_logo)) {
            $updateDataLogo = [
                //'media_id' => $request->media_id_logo,
                'members_area_id' => $membersAreaId,
                'media_type' => 'Logo',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_logo, $updateDataLogo));
        }
        if (isset($request->media_id_thumb)) {
            $updateDataThumb = [
                //'media_id' => $request->media_id_thumb,
                'members_area_id' => $membersAreaId,
                'media_type' => 'Thumbnail',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_thumb, $updateDataThumb));
        }
        return $mediaUpdated;
    }

    public function getMemberships($userId, $itemsPerPage, $membersAreaId = null)
    {
        // Cria a query base
        $query = MembersArea::select('id', 'area_name', 'area_type')
            ->where('user_id', $userId)
            ->where('is_deleted', 0)
            ->where('is_blocked', 0)
            ->with(['modules' => function ($query) {
                $query->select('id', 'module_name', 'is_active', 'is_blocked', 'members_area_id')
                    ->with(['media:modules_id,s3_url,media_type'])
                    ->where('is_deleted', 0)
                    ->where('is_blocked', 0);
            }]);

        // Adiciona a condição para filtrar por `members_area_id` se fornecido
        if ($membersAreaId) {
            $query->where('id', $membersAreaId);
        }

        // Retorna a query paginada
        return $query->paginate($itemsPerPage);
    }
}
