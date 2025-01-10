<?php

namespace App\Http\Requests\Offering;

use Illuminate\Foundation\Http\FormRequest;

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
            'fake_price' => 'numeric',
            'offer_type' => 'string|min:1',
            'recurrently_installments' => 'integer',
            'enable_billet' => 'boolean',
            'enable_card' => 'boolean',
            'enable_pix' => 'boolean',
            'sale_completed_page_url' => 'nullable|string',
            'charge_type' => 'string'
        ];
    }
}
