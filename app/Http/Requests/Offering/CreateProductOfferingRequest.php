<?php

namespace App\Http\Requests\Offering;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateProductOfferingRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'offer_name' => 'required',
            'utmify_token' => 'nullable|string',
            'price' => 'required|numeric|min:5',
            'fake_price' => 'required|numeric|gt:price',
            'offer_type' => 'required|string',
            'recurrently_installments' => 'required|integer',
            'enable_card' => 'required|boolean',
            'enable_pix' => 'required|boolean',
            'enable_billet' => 'required|boolean',
            'charge_type' => 'required|string',
            'sale_completed_page_url' => 'nullable|string',
            // Regra personalizada usando closure
            'payment_method_check' => function ($attribute, $value, $fail) {
                if (!$this->enable_card && !$this->enable_pix && !$this->enable_billet) {
                    $fail('Ao menos um método de pagamento deve estar ativo.');
                }
            },
            'integration_faceboook' => 'nullable|array',
            'integration_faceboook.*.pixel_id' => 'required|string',
            'integration_faceboook.*.access_token' => 'nulllable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->enable_card && !$this->enable_pix && !$this->enable_billet) {
                $validator->errors()->add('payment_method_check', 'Ao menos um método de pagamento deve estar ativo.');
            }
        });
    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }

    public function messages(){

        return [
            'offer_name.required' => 'O nome da oferta é obrigatório e não foi preenchido',
            'fake_price.required' => 'O Preço fictício não foi preenchido',
            'fake_price.gt' => 'O Preço fictício deve ser maior que o valor atual da oferta',
            'price.required' => 'O preço não foi preenchida',
            'price.min' => 'O preço Mínimo deve ser de pelo menos R$9,00',
            'offer_type.required' => 'O tipo da oferta deve ser informado se é recorrente ou pagamento único',
            'recurrently_installments.required' => 'O tipo de recorrência da oferta não foi enviada',
            'enable_billet.required' => 'O pagamento por boleto não foi indicado se está ativo ou desativado',
            'enable_card.required' => 'O campo de Pagamento com Cartão não foi indicado se está ativo ou desativado',
            'enable_pix.required' => 'O campo de Pagamento com PIX não foi indicado se está ativo ou desativado',
            'sale_completed_page_url.required' => 'O campo de página de obrigado não foi informado qual utilizar',
            'product_id.required' => 'O id do produto não foi informado'
        ];

    }

}
