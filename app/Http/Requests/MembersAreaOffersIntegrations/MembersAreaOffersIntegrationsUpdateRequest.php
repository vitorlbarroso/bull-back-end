<?php

namespace App\Http\Requests\MembersAreaOffersIntegrations;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MembersAreaOffersIntegrationsUpdateRequest extends FormRequest
{

//        {
//        "members_area_id": 123,            // ID da área de membros (obrigatório)
//        "product_offering_id":123,
//        "modules": [                      // Lista de módulos com status de seleção
//            {
    //            "id": 101,             // ID do módulo
    //            "is_selected": true           // Status de seleção (boolean)
//            },
//            {
    //            "id": 102,
    //            "is_selected": false
//            }
//        ]
//        }
    public function rules(): array
    {
        return [
            'product_offering_id' => 'required|exists:products_offerings,id',
            'modules' => 'required|array|min:1',
            'modules.*.id' => 'required|integer|exists:modules,id',
            'modules.*.is_selected' => 'required|boolean'
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
    /**
     * Mensagens personalizadas para validação.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'product_offering_id.required' => 'A oferta que será vinculada não foi informada',
            'members_area_id.integer' => 'O ID da área de membro deve ser um número inteiro.',
            'members_area_id.exists' => 'A área de membro informada não existe.',
            'product_offering_id.exists' => 'oferta informada não existe.',
            'modules.required' => 'É necessário informar pelo menos um módulo.',
            'modules.*.id.required' => 'O ID do módulo é obrigatório.',
            'modules.*.id.integer' => 'O ID do módulo deve ser um número inteiro.',
            'modules.*.id.exists' => 'O módulo informado não existe.',
            'modules.*.is_selected.required' => 'O campo is_selected é obrigatório.',
            'modules.*.is_selected.boolean' => 'O campo is_selected deve ser verdadeiro ou falso.'
        ];
    }


}
