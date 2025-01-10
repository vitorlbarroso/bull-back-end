<?php

namespace App\Http\Requests\Modules;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateModulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'module_name' => ['required','string:min3'],
            'media_id_banner' => ['integer', 'exists:media,id'],
            'media_id_thumb' => ['integer', 'exists:media,id'],
            'members_area_id' => ['required', 'integer']
        ];
    }

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
            'module_name.required' => 'o Nome do Módulo é obrigatório',
            'module_name.min' => 'O Nome do Módulo deve conter pelo menos 3 caracteres',
            'members_area_id.required' => 'A area de membros não foi informada',
            'media_id_banner.exists' => 'A imagem do banner informada não existe',
            'media_id_thumb.exists' => 'A imagem da Thumbnail informada não existe'
        ];
    }


}
