<?php

namespace App\Http\Requests\Checkout;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;


class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_offering_id' => ['required', 'exists:products_offerings,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000'));

    }

    public function messages(){

        return [
            'product_offering_id.required' => 'A oferta não foi informada!',
            'product_offering_id.exists' => 'A oferta passada não foi encontrada ou é invalida',
        ];

    }

}
