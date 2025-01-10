<?php

namespace App\Http\Requests\Modules;

use App\Http\Helpers\Responses;
use App\Models\MembersArea;
use App\Models\Modules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateModulesRequest extends FormRequest
{
    protected $validatedModulo;

    public function rules(): array
    {

        return [
            'module_name' => ['string:min3'],
            'media_id_banner' => ['integer'],
            'media_id_thumb' => ['integer'],
            'is_active' => [ 'boolean']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }
    public function validateModules(): bool
    {
        $id = $this->route('id');
        $getModulo = Modules::join('members_area', 'modules.members_area_id', '=', 'members_area.id')
            ->where('members_area.user_id', Auth::id())
            ->where('modules.id', $id)
            ->select('members_area.*', 'modules.*')
            ->first();

        if (!$getModulo) {
            // Se a área de membros não foi encontrada
            $this->validator->errors()->add('module_id', __('O Módulo informado não foi encontrado ou não pertence a este usuário.'));
            return false;
        }

// Validando se o módulo está deletado
        if ($getModulo->is_deleted) {
            $this->validator->errors()->add('module_id', __('O Módulo informado está deletado.'));
            return false;
        }

// Validando se o módulo está bloqueado
        if ($getModulo->is_blocked) {
            $this->validator->errors()->add('module_id', __('O Módulo informado está bloqueado.'));
            return false;
        }

// Validando se a área de membros está deletada
        if ($getModulo->members_area_is_deleted) {
            $this->validator->errors()->add('members_area_id', __('A área de membros desse Módulo está deletada.'));
            return false;
        }

// Validando se a área de membros está bloqueada
        if ($getModulo->members_area_is_blocked) {
            $this->validator->errors()->add('members_area_id', __('A área de membros desse Módulo está bloqueada.'));
            return false;
        }
        $this->validatedModulo= $getModulo;

        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->validateModules()) {
            }
        });
    }

    public function validatedModule()
    {
        return $this->validatedModulo;
    }

    public function messages()
    {
        return [
        ];
    }
    public function authorize(): bool
    {
        return true;
    }
}
