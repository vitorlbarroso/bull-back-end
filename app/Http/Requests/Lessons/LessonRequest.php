<?php

namespace App\Http\Requests\Lessons;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LessonRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lesson_name' => ['required'],
            'description' => ['nullable'],
            'comments_allow' => ['boolean'],
            'modules_id' => ['required', 'exists:modules,id'],
            'default_release_lesson' => ['in:0,7,custom'],
            'custom_release_date' => ['date'],
            'media_id_thumb' => ['integer'],
            'media_id_attachment' => ['integer'],
            'media_id_content' => ['integer'],
            'order_bump_offer_id' => ['nullable', 'array'],
            'order_bump_offer_id.*.id' => 'required_with:order_bump_offer_id|integer|exists:products_offerings,id', // Valida se o ID é inteiro e existe na tabela
        ];
    }

//"order_bump_offer_id": [
//        {  "id": 3  },
//       {  "id": 4  }
//   ]
    public function authorize(): bool
    {
        return true;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }


    public function messages()
    {
        return [
            'lesson_name.required' => 'O Nome do Aula é obrigatório',
            'description.min' => 'A descrição é obrigatória',
            'modules_id.required' => 'O Módulo dessa aula não foi informado',
            'default_release_lesson.required' => 'A liberação dessa aula, não foi enviada no padrão esperado que deveria ser, 0, 7 ou custom',
            'modules_id.exists' => 'O Módulo dessa aula não foi encontrado',

        ];
    }
}
