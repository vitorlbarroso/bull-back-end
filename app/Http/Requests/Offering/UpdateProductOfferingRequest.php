<?php

namespace App\Http\Requests\Offering;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductOfferingRequest extends FormRequest
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
            'offer_name' => 'string|min:3',
            'price' => 'numeric',
            'utmify_token' => 'nullable|string',
            'fake_price' => 'numeric',
            'offer_type' => 'string|min:1',
            'recurrently_installments' => 'integer',
            'enable_billet' => 'boolean',
            'enable_card' => 'boolean',
            'enable_pix' => 'boolean',
            'sale_completed_page_url' => 'nullable|string',
            'charge_type' => 'string',
            'removed_facebook_pixel' => 'nullable|array',
            'removed_facebook_pixel.*.id' => 'required|integer|distinct|min:1',
            'integration_faceboook' => 'nullable|array',
            'integration_faceboook.*.pixel_id' => 'required|string',
            'integration_faceboook.*.access_token' => 'nulllable|string',
        ];
    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }
}
