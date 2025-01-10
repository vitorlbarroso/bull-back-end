<?php

namespace App\Http\Requests\MembersArea;

use App\Http\Helpers\Responses;
use App\Models\MembersArea;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateMembersAreaRequest extends FormRequest
{
    protected $validatedArea;

    public function rules(): array
    {

        return [
            'area_name' => 'string|min:3',
            'area_type' => ['string', 'in:file,stream'],
            'slug' => ['string'],
            'comments_allow' => ['boolean'],
            'is_comments_auto_approve' => ['boolean'],
            'layout_type' => ['string', 'in:carrossel,fileira'],
            'is_active' => 'boolean',
            'media_id_logo' => ['integer'],
            'media_id_thumb' => ['integer']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR(''.$validator->errors()->first(), $validator->errors(), '-1000', 400));
    }
    public function validateAreaMember(): bool
    {
        $id = $this->route('id');
        $user = Auth::user();

        $getAreaMember = MembersArea::where('id', $id)
            ->where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->where('is_blocked', 0)
            ->first();

        if (!$getAreaMember) {
            $this->validator->errors()->add('id', __('A Área informada não foi encontrada'));
            return false;
        }

        if ($getAreaMember->is_blocked) {
            $this->validator->errors()->add('id', __('Não é possível editar uma Área Bloqueada'));
            return false;
        }

        if ($getAreaMember->is_deleted) {
            $this->validator->errors()->add('id', __('Não é possível editar uma Área Deletada'));
            return false;
        }
        $this->validatedArea = $getAreaMember;
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->validateAreaMember()) {
            }
        });
    }

    public function validatedArea()
    {
        return $this->validatedArea;
    }

    public function messages()
    {
        return [
            'area_name.min' => 'O nome da área deve conter no mínimo 3 caracteres',
            'area_name.string' => 'O campo area_name deve ser uma string.',
            'area_type.in' => 'O Tipo de Área selecionado é invalido',
            'layout_type.in' => 'O Tipo de Layout selecionado é invalido',
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
