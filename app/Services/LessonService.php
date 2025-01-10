<?php

namespace App\Services;

use App\Http\Helpers\Responses;
use App\Models\Lesson;
use App\Models\lessonMedia;
use App\Models\Media;
use App\Models\Modules;
use App\Models\OrderBump;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonService
{

    public function __construct(protected  MediaService $mediaService, protected MembersAreaService $membersAreaService)
    {
    }

    public function Update($lesson_to_update, $new_data_lesson, $transactionid = null)
    {
        $new_data_lesson['id'] = $lesson_to_update->id;
        Log::info("|" . $transactionid. "| Método Update de LessonService |",
            ['Lesson Atual' => $lesson_to_update, 'Dados da nova Aula' => $new_data_lesson]);

        return DB::transaction(function () use ($lesson_to_update, $new_data_lesson, $transactionid) {

            if (isset($new_data_lesson->media_id_thumb)) {
                $this->deleteMediaByType($lesson_to_update->id, 'Thumbnail');
            }

            if (isset($new_data_lesson->media_id_attachment)) {
                $this->deleteMediaByType($lesson_to_update->id, 'Attachment');
            }

            if (isset($new_data_lesson->media_id_content)) {
                $this->deleteMediaByType($lesson_to_update->id, 'Content');
            }

            $mediaUpdated = $this->updateMediaToLesson( (object) $new_data_lesson, $new_data_lesson['id'], $transactionid);

            $new_data_lesson = collect($new_data_lesson)->except(['media_id_logo', 'media_id_thumb', 'media_id_content'])->toArray();
            $lesson_to_update->update($new_data_lesson);
            $updated_lesson= $lesson_to_update->fresh();
            $updated_lesson->media = $mediaUpdated;
            return $this->formatAreaData($updated_lesson);
        });
    }

   public function deleteMediaByType($lessonId, $fileType) {
        LessonMedia::where('lesson_id', $lessonId)
            ->whereIn('media_id', function ($query) use ($fileType) {
                $query->select('lesson_media.media_id')
                    ->from('lesson_media')
                    ->join('media', 'lesson_media.media_id', '=', 'media.id')
                    ->where('media.file_type', $fileType);
            })
            ->delete();
    }

    public function show($itemsPerPage, $membersAreaId = null)
    {
        // listar os módulos com suas aulas
    }


    public function formatAreaData($lesson)
    {
        return [
            'id' => $lesson->id,
            'lesson_name' => $lesson->lesson_name,
            'modules_id' => $lesson->modules_id,
            'comments_allow' => $lesson->comments_allow,
            'is_active' => $lesson->is_active,
            'default_release_lesson' => $lesson->default_release_lesson,
            'media' => collect($lesson->media)->map(function ($media) use($lesson) {
                return [
                    'modules_id' => $lesson->modules_id,
                    's3_url' => $media->s3_url,
                    'media_type' => $media->media_type
                ];
            })->toArray(),
            'Order_bump' => collect($lesson->order_bump)->map(function ($bump) {
                return [
                    'id' => $bump->id,
                    'product_offerings_id' => $bump->products_offerings_id
                ];
            })->toArray()
        ];
    }

    public function createLesson($lesson, $request)
    {
        return DB::transaction(function () use ($lesson, $request) {
            // Criar a Aula
            $create_lesson = Lesson::create($lesson);
            // Atualizar as mídias relacionadas (banner e thumbnail)
            Log::info("|" . request()->header('x-transaction-id'). "| Método CreateLesson |",
                ['Aula sendo criada ainda em transação, podendo sofrer Rollback em caso de falha' => $create_lesson]);

            $mediaUpdated = $this->updateMediaToLesson($request, $create_lesson->id, request()->header('x-transaction-id'));
            $store_order_bump= $this->storeOrderBump($request, $create_lesson->id, request()->header('x-transaction-id'));
            // Associar mídias ao módulo
            $create_lesson->order_bump = $store_order_bump;
            $create_lesson->media = $mediaUpdated;
            return $this->formatAreaData($create_lesson);
        });
    }

    public function storeOrderBump($request, $create_lesson_id, $transactionid = null)
    {
        // Valida se o atributo order_bump_offer_id está presente no request
        Log::info("|" . $transactionid. "| Método storeOrderBump de LessonService |",
            ['Request ' => $request, 'Id da Aula' => $create_lesson_id]);
        // Se a validação passar, insere o registro na tabela order_bumps
        try {
            if (isset($request->order_bump_offer_id)) {
                $createdOrderBumps = [];
                foreach ($request->order_bump_offer_id as $offer) {
                    $createdOrderBump = OrderBump::create([
                        'checkout_id' => null,
                        'products_offerings_id' => $offer['id'],
                        'lesson_id' => $create_lesson_id,
                    ]);
                    $createdOrderBumps[] = $createdOrderBump;
                }
                return $createdOrderBumps;
            }
        }
        catch (\Throwable $th) {
            Log::error('Ocorreu um erro ao criar o order bump para a Aula', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao criar o order bump', null, -9999, 500);
        }
    }

    private function updateMediaToLesson($request, $lessonId, $transactionid = null)
    {
        $mediaUpdated = collect();
        Log::info("|" . $transactionid. "| Método updateMediaToLesson de LessonService |",
            ['Request ' => $request, 'Id da Aula' => $lessonId]);

        if (isset($request->media_id_thumb)) {
            $updateDataThumb= [
                'media_id' => $request->media_id_thumb,
                'media_type' => 'Thumbnail',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_thumb, $updateDataThumb));
            Log::info("|" . $transactionid. "| Relacionando a thumbnail a esta aula|",
                ['Request ' => $updateDataThumb, 'Id da Aula' => $lessonId]);

            DB::table('lesson_media')->insert([
                'lesson_id' => $lessonId,
                'media_id' => $request->media_id_thumb,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (isset($request->media_id_attachment)) {
            $updateDataThumb= [
                'media_id' => $request->media_id_attachment,
                'media_type' => 'Attachment',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_attachment, $updateDataThumb));
            Log::info("|" . $transactionid. "| Relacionando o Anexo enviado a esta aula|",
                ['Request ' => $updateDataThumb, 'Id da Aula' => $lessonId]);

            DB::table('lesson_media')->insert([
                'lesson_id' => $lessonId,
                'media_id' => $request->media_id_attachment,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (isset($request->media_id_content)) {
            $updateDataThumb= [
                'media_id' => $request->media_id_content,
                'media_type' => 'Content',
            ];
            $mediaUpdated->push($this->mediaService->UpdateMediaById($request->media_id_content, $updateDataThumb));
            Log::info("|" . $transactionid. "| Relacionando o Conteudo do tipo File enviado a esta aula|",
                ['Request ' => $updateDataThumb, 'Id da Aula' => $lessonId]);

            DB::table('lesson_media')->insert([
                'lesson_id' => $lessonId,
                'media_id' => $request->media_id_content,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $mediaUpdated;
    }

}
