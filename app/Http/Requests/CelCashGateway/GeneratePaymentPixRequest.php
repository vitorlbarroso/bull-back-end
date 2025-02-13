<?php

namespace App\Http\Requests\CelCashGateway;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePaymentPixRequest extends FormRequest
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
            'principal_offer' => 'required|integer',
            'orderbumps' => 'array',
            'orderbumps.*.id' => 'required|integer',
            'customer_name' => 'string',
            'customer_document' => 'string',
            'customer_email' => 'string',
            'customer_phone' => 'string',
        ];
    }
}
