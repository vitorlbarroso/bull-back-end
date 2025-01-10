<?php

namespace App\Http\Requests\MembersArea;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateMembersAreaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'area_name' => ['required', 'string:min3'],
            'area_type' => ['required', 'string', 'in:file,stream'],
            'slug' => ['required', 'string'],
            'comments_allow' => ['boolean'],
            'is_comments_auto_approve' => ['boolean'],
            'layout_type' => ['required', 'string', 'in:carrossel,fileira'],
            'media_id_logo' => ['integer'],
            'media_id_thumb' => ['integer']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }

    public function messages()
    {
        return [
            'area_name.required' => 'o email deve ser um email válido',
            'area_type.required' => 'O tipo da área não foi informado',
            'area_type.in' => 'O tipo da área informada não consta como tipo de área disponível',
            'slug.required' => 'o Slug não foi enviada',
            'comments_allow.required' => 'A permissão de comentários não foi informada',
            'is_comments_auto_approve.required' => 'O nome da descrição no cartao de crédito não foi preenchido',
            'layout_type.required' => 'O tipo do layout não foi informado',
            'layout_type.in' => 'O tipo do layout não é compatível com os tipos disponíveis'
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
