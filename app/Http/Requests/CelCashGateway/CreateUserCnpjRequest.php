<?php

namespace App\Http\Requests\CelCashGateway;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserCnpjRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'document_cpf' => 'required|string',
            'document_cnpj' => 'required|string',
            'name_display' => 'required|string',
            'phone' => 'required|string',
            'cnae' => 'required|string',
            'type_company_cnpj' => 'required|string',
            'address_zipcode' => 'required|string',
            'address_street' => 'required|string',
            'address_number' => 'required|string',
            'address_neighborhood' => 'required|string',
            'address_city' => 'required|string',
            'address_state' => 'required|string',
            'last_contract' => 'required_if:type_company_cnpj,ltda,eireli,slu|string',
            'cnpj_card' => 'required_if:type_company_cnpj,mei,individualEntrepreneur|string',
            'monthly_income' => 'required|integer',
            'about' => 'required|string',
            'social_media_link' => 'required|string',
            'responsible_document_cpf' => 'required|string',
            'responsible_name' => 'required|string',
            'mother_name' => 'required|string',
            'birth_date' => 'required|string',
            'rg_selfie' => 'required|string',
            'rg_front' => 'required|string',
            'rg_back' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo "name" é obrigatório e não foi preenchido',
            'name.string' => 'O campo "name" precisa ser do tipo string',
            'document.required' => 'O campo "document" é obrigatório e não foi preenchido',
            'document.string' => 'O campo "document" precisa ser do tipo string',
            'phone.required' => 'O campo "phone" é obrigatório e não foi preenchido',
            'phone.string' => 'O campo "phone" precisa ser do tipo string',
            'address_zipcode.required' => 'O campo "address_zipcode" é obrigatório e não foi preenchido',
            'address_zipcode.string' => 'O campo "address_zipcode" precisa ser do tipo string',
            'address_street.required' => 'O campo "address_street" é obrigatório e não foi preenchido',
            'address_street.string' => 'O campo "address_street" precisa ser do tipo string',
            'address_number.required' => 'O campo "address_number" é obrigatório e não foi preenchido',
            'address_number.string' => 'O campo "address_number" precisa ser do tipo string',
            'address_neighborhood.required' => 'O campo "address_neighborhood" é obrigatório e não foi preenchido',
            'address_neighborhood.string' => 'O campo "address_neighborhood" precisa ser do tipo string',
            'address_city.required' => 'O campo "address_city" é obrigatório e não foi preenchido',
            'address_city.string' => 'O campo "address_city" precisa ser do tipo string',
            'address_state.required' => 'O campo "address_state" é obrigatório e não foi preenchido',
            'address_state.string' => 'O campo "address_state" precisa ser do tipo string',
        ];
    }
}
