<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Lessons\LessonRequest;
use App\Http\Requests\Lessons\UpdateLessonRequest;
use App\Models\Lesson;
use App\Models\Modules;
use App\Services\LessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function __construct(protected LessonService $lesson_service)
    {

    }
    public function index(Request $request)
    {
        $user = Auth::user();
        $itemsPerPage = $request->query('items_per_page', 10);
        $get_lessons = Lesson::select()
            ->with('lesson_media:id')
            ->where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->orderByDesc('id')
            ->paginate($itemsPerPage);

        return Responses::SUCCESS('', $get_lessons);
    }

    public function store(LessonRequest $request)
    {
        $lesson= $request->validated();
        $user = Auth::user();

        $getModule= Modules::where('id', $request->modules_id)
            ->where('is_blocked', 0)
            ->where('is_deleted', 0)
            ->whereHas('members_area', function($query) use ($user) {
                $query->where('user_id', $user->id)
                        ->where('is_deleted', 0)
                        ->where('is_blocked', 0);
            })
            ->first();
        if (!$getModule) {
            return Responses::ERROR('O Módulo selecionado está bloqueado ou não foi localizado', null, -1100, 400);
        }
        try {
            $create_lesson = $this->lesson_service->createLesson($lesson, $request);
            return Responses::SUCCESS('Aula criada com sucesso',$create_lesson, 201);

        } catch (\Throwable $th) {
            Log::error('Não foi possível criar a Aula', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar a Aula', null, '-9999', 400);
        }
    }

    public function show(Lesson $lesson)
    {
        return $lesson;
    }

    public function update(UpdateLessonRequest $request)
    {
        $lesson_to_update = $request->validatedLesson();
        $data_lesson_received = $request->validated();
        try {
            $formatted_data = $this->lesson_service->Update($lesson_to_update,$data_lesson_received, request()->header('x-transaction-id'));
            return Responses::SUCCESS('Aula atualizada com sucesso', $formatted_data);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível atualizar a Aula', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível atualizar a Aula. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function destroy(UpdateLessonRequest $lesson)
    {
        try {
            $validated_lesson = $lesson->validatedLesson();
            $updated_lesson = $validated_lesson->update(['is_deleted' => true]);
            return Responses::SUCCESS('Aula excluída com sucesso', $validated_lesson);

        } catch (\Exception $e) {
            Log::error('Não foi possível Deletar a Aula', ['error' => $e->getMessage()]);
            return Responses::ERROR('Não foi possível deletar a Aula. Erro genérico não mapeado', null, '-9999');
        }

    }
}
