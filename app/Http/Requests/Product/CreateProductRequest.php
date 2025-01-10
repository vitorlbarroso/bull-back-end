<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Helpers\Responses;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'product_name'=> 'required|min:3',
            'product_description' => 'required|min:7',
            'product_type' => 'required',
            'product_category' => 'required',
            'email_support' => 'required|string',
            /*'whatsapp_support' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'card_description' => 'required|min:7',
            'refund_time' => 'required|integer'*/
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }

    public function messages()
    {
        return [
            'product_name.required' => 'O nome do Produto é obrigatório e não foi preenchido',
            'email_support.required' => 'O email de suporte é obrigatório e não foi preenchido',
            'email_support.email' => 'o email deve ser um email válido',
            'product_description.required' => 'A descrição do produto é obrigatória e não foi preenchida',
            'product_type.required' => 'o tipo é obrigatorio e não foi enviado',
            'product_category.required' => 'A categoria não foi enviada',
            'whatsapp_support.required' => 'O telefone de whatsapp não foi preenchido',
//            'card_description' => 'O nome da descrição no cartao de crédito não foi preenchido',
            'refund_time' => 'O campo de reembolso não foi preenchido',
        ];
    }
}
