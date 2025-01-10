<?php

namespace App\Services;

use App\Http\Helpers\Responses;
use App\Models\Lesson;
use App\Models\MembersArea;
use App\Models\Modules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleService
{

    public function __construct(protected  MediaService $mediaService, protected MembersAreaService $membersAreaService)
    {
    }

    public function Update($modulo_to_update, $new_data_modulo, $transactionid = null)
    {
        $new_data_modulo['id'] = $modulo_to_update->id;
        Log::info("|" . $transactionid. "| Método Update de ModuleService |",
            ['Módulo Atual' => $modulo_to_update, 'Dados do novo módulo' => $new_data_modulo]);

        return DB::transaction(function () use ($modulo_to_update, $new_data_modulo, $transactionid) {

            $this->mediaService->RemoveOldMedia((object) $new_data_modulo, $transactionid,'modules_id');
            $mediaUpdated = $this->updateMediaToModules( (object) $new_data_modulo, $new_data_modulo['id'], $transactionid);

            $new_data_modulo = collect($new_data_modulo)->except(['media_id_logo', 'media_id_thumb'])->toArray();
            $modulo_to_update->update($new_data_modulo);
            $updated_members_area = $modulo_to_update->fresh();
            $updated_members_area->media = $mediaUpdated;
            return $this->formatAreaData($updated_members_area);;
        });
    }

    public function show($itemsPerPage, $membersAreaId = null)
    {
        // listar os módulos com suas aulas
    }



    public function getMedia($modulo)
    {
        return Modules::with('media:members_area_id,s3_url,media_type')
            ->find($modulo->id);
    }

    public function formatAreaData($modulo)
    {
        return [
            'id' => $modulo->id,
            'members_area' => [
                'members_area_id' => $modulo->members_area_id,
            ],
            'module_name' => $modulo->module_name,
            'is_active' => $modulo->is_active,
            'is_blocked' => $modulo->is_blocked,
            'media' => $modulo->media->map(function ($media) {
                return [
                    'modules_id' => $media->modules_id,
                    's3_url' => $media->s3_url,
                    'media_type' => $media->media_type
                ];
            }, is_array($modulo->media) ? $modulo->media : [])
        ];
    }

    public function createModule($modulo, $request)
    {
        return DB::transaction(function () use ($modulo, $request) {
            // Criar o módulo
            $create_module = Modules::create($modulo);
            // Atualizar as mídias relacionadas (banner e thumbnail)
            $mediaUpdated = $this->updateMediaToModules($request, $create_module->id, request()->header('x-transaction-id'));
            // Associar mídias ao módulo
            $create_module->media = $mediaUpdated;
            return $this->formatAreaData($create_module);
        });
    }

    private function updateMediaToModules($request, $moduloId, $transactionid = null)
    {
        $mediaUpdated = collect();
        Log::info("|" . $transactionid. "| Método updateMediaToModules de ModuleService |",
            ['Request ' => $request, 'Id do Módulo' => $moduloId]);

        if (isset($request->media_id_banner)) {
            $updateDataBanner= [
                'modules_id' => $moduloId,
                'media_type' => 'Banner',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_banner, $updateDataBanner));
        }
        if (isset($request->media_id_thumb)) {
            $updateDataThumb = [
                'modules_id' => $moduloId,
                'media_type' => 'Thumbnail',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_thumb, $updateDataThumb));
        }
        return $mediaUpdated;
    }

    public function showByModules($modules_id = null)
    {
        // Busca e paginação de Aulas delegada ao serviço
        $lessons = $this->getLessons(Auth::id(), $modules_id);
        // Formatação dos dados
        return $this->formatLessons($lessons);
    }

    public function getLessons($userId, $modules_id = null)
    {
        $query = DB::table('modules AS mo')
            ->select(
                'mo.id AS module_id',
                'mo.module_name',
                'ma.area_name',
                'ma.user_id',
                'l.id AS lesson_id',
                'l.lesson_name',
                'l.description',
                'l.is_active',
                DB::raw("COALESCE(MAX(CASE WHEN m.media_type = 'Thumbnail' THEN m.s3_url END), '') AS lesson_thumb"),
                DB::raw("COALESCE(MAX(CASE WHEN m.media_type = 'Content' THEN m.s3_url END), '') AS content"),
                DB::raw("COALESCE(MAX(CASE WHEN m.media_type = 'Attachment' THEN m.s3_url END), '') AS attachment"),
                DB::raw("(SELECT s3_url FROM media AS me WHERE me.modules_id = mo.id AND me.media_type = 'Thumbnail') AS module_thumb")
            )
            ->leftJoin('lessons AS l', function($join) {
                $join->on('l.modules_id', '=', 'mo.id')
                    ->where('l.is_deleted', '=', 0);
            })
            ->leftJoin('lesson_media AS lm', 'l.id', '=', 'lm.lesson_id')
            ->leftJoin('media AS m', 'lm.media_id', '=', 'm.id')
            ->leftJoin('members_area AS ma', 'mo.members_area_id', '=', 'ma.id')
            ->where('mo.is_blocked', '=', 0)
            ->where('m.upload_status', '=', 'complete')
            ->where('ma.user_id', '=', $userId);

// Verifica se o módulo ID foi fornecido e aplica a condição
        if (!is_null($modules_id)) {
            $query->where('mo.id', $modules_id);
        }
        $query->groupBy('mo.id', 'ma.area_name', 'ma.user_id', 'l.id');
// Executa a query
        return $query->get();
    }

    function formatLessons(Collection $lessons)
    {
        // Verifica se a coleção possui itens
        if ($lessons->isEmpty()) {
            return [
                'lessons' => []
            ];
        }
        // Extrai o módulo ID e o thumb do primeiro item (assumindo que todos têm o mesmo module_id e module_thumb)
        $moduleId = $lessons->first()->module_id;
        $moduleThumb = $lessons->first()->module_thumb;

        if ($lessons->first()->lesson_id == null) {
            return [
                'module_id' => $moduleId,
                'module_thumb' => $moduleThumb,
                'lessons' => []
            ];
        }

        // Mapeia cada lição para o formato desejado
        $formattedLessons = $lessons->map(function ($lesson) {
            return [
                'id' => $lesson->lesson_id,
                'lesson_name' => $lesson->lesson_name,
                'description' => $lesson->description,
                'is_active' => $lesson->is_active,
                'media' => [
                    'lesson_thumb' => $lesson->lesson_thumb,
                    'lesson_content' => $lesson->content,
                    'lesson_attachment' => $lesson->attachment
                ]
            ];
        });

        // Retorna o array no formato desejado
        return [
            'module_id' => $moduleId,
            'module_thumb' => $moduleThumb,
            'lessons' => $formattedLessons->toArray(),
        ];
    }


}
