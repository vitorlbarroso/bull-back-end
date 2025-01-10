<?php

namespace App\Http\Requests\Lessons;

use App\Http\Helpers\Responses;
use App\Models\Lesson;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateLessonRequest extends FormRequest
{
    protected $validateLesson;
    public function rules(): array
    {
        return [
            'lesson_name' => ['string:min3'],
            'description' => ['nullable','string'],
            'is_active' => 'boolean',
            'comments_allow' => ['boolean'],
            'modules_id' => ['nullable', 'exists:modules,id'],
            'default_release_lesson' => ['in:0,7,custom'],
            'custom_release_date' => ['date'],
            'media_id_thumb' => ['integer'],
            'media_id_attachment' => ['integer'],
            'media_id_content' => ['integer'],
            'order_bump_offer_id' => ['nullable', 'array'],
            'order_bump_offer_id.*.id' => 'required_with:order_bump_offer_id|integer|exists:products_offerings,id', // Valida se o ID é inteiro e existe na tabela
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos preenchidos inválidos', $validator->errors(), '-1000', 400));
    }

    public function validateLesson(): bool
    {
        $id = $this->route('id');
        $getLesson = Lesson::where('id', $id)
            ->whereHas('modules', function ($query) {
                $query->whereHas('members_area', function ($query) {
                    $query->where('user_id', Auth::id());
                });
            })
            ->with(['modules.members_area']) // Carrega as relações necessárias
            ->first();

        if (!$getLesson) {
            // Se a área de membros não foi encontrada
            $this->validator->errors()->add('id', __('A aula informada não foi encontrada ou não pertence a este usuário.'));
            return false;
        }

// Validando se o módulo está deletado
        if ($getLesson->is_deleted) {
            $this->validator->errors()->add('id', __('A aula informada já está deletada.'));
            return false;
        }

        if ($getLesson->modules->is_deleted) { // Verifica o campo deleted_at
            $this->validator->errors()->add('module_id', __('O Módulo que essa aula pertence está deletado.'));
            return false;
        }

// Validando se a área de membros está deletada
        if ($getLesson->modules->members_area->is_deleted) {
            $this->validator->errors()->add('members_area_id', __('A área de membros dessa Aula está deletada.'));
            return false;
        }

// Verifica se a área de membros foi encontrada e está deletada
        if (!$getLesson->modules->members_area ) { // Verifica o campo deleted_at
            $this->validator->errors()->add('members_area_id', __('A área de membros dessa Aula não foi encontrada.'));
            return false;
        }

        $this->validateLesson= $getLesson;

        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->validateLesson()) {
            }
        });
    }

    public function validatedLesson()
    {
        return $this->validateLesson;
    }
    public function authorize(): bool
    {
        return true;
    }
}
