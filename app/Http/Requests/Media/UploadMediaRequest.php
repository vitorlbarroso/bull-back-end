<?php

namespace App\Http\Requests\Media;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|max:6048',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }

    public function messages(){

        return [
            'file.required' => 'O file não foi enviado',
            'file.mimes' => 'O Tipo do formato enviado não é permitido',
        ];

    }
}
