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

        \Log::debug('Request Recebida:', [
            'headers' => request()->headers->all(),
            'all' => request()->all(),
            'files' => request()->allFiles(),
        ]);
        if (!$this->hasFile('file')) {
            \Log::debug('Falha na Validação - Arquivo não encontrado na requisição.', ["objeto recebido no request" =>  $this->all()]);
        } else {
            \Log::debug(' Validação - Arquivo encontrado na requisição:', [
                'original_name' => $this->file('file')->getClientOriginalName(),
                'size' => $this->file('file')->getSize(),
                'mime_type' => $this->file('file')->getMimeType(),
            ]);
        }

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }

    public function messages(){

        return [
            'file.required' => 'O file não foi enviado',
            'file.mimes' => 'O Tipo do formato enviado não é permitido',
        ];

    }
}
