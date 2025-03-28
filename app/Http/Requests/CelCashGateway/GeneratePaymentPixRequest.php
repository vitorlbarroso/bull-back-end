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
            'customer_document' => 'nullable|string',
            'customer_email' => 'string',
            'customer_phone' => 'nullable|string',
            'customer_zipcode' => 'nullable|string',
            'customer_state' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_number' => 'nullable|string',
            'customer_complement' => 'nullable|string',
            'pixel_data' => 'nullable|array',
            'pixel_data.offer_id' => 'nullable|integer',
            'pixel_data.event_name' => 'required|string|max:255',
            'pixel_data.event_source_url' => 'nullable|url',
            'pixel_data.event_time' => 'nullable|integer',
            'pixel_data.action_source' => 'nullable|string|in:website,app,other', // Ajuste conforme o contexto
            'pixel_data.user_data' => 'nullable|array',
            'pixel_data.user_data.em' => 'nullable|array', //email
            'pixel_data.user_data.em.*' => 'nullable|string', // Hash de 64 caracteres
            'pixel_data.user_data.ph' => 'nullable|array',
            'pixel_data.user_data.ph.*' => 'nullable|string', // formato 5511985236631
            'pixel_data.user_data.ct' => 'nullable|array',
            'pixel_data.user_data.ct.*' => 'nullable|string', //cidade sem espacos e acentos
            'pixel_data.user_data.client_ip_address' => 'nullable|string',
            'pixel_data.user_data.client_user_agent' => 'nullable|string|max:255',
            'pixel_data.user_data.fbc' => 'nullable|string', // identificação do clique no Facebook está armazenado no cookie
            'pixel_data.user_data.fbp' => 'nullable|string', // identificação do navegador no Facebook está armazenado no cookie
            'pixel_data.user_data.fn' => 'nullable|array',
            'pixel_data.user_data.fn.*' => 'nullable|string',
            'pixel_data.user_data.country' => 'nullable|array',
            'pixel_data.user_data.country.*' => 'nullable|string',
            'pixel_data.user_data.zp' => 'nullable|array',
            'pixel_data.user_data.zp.*' => 'nullable|string',
            'pixel_data.user_data.ge' => 'nullable|array',
            'pixel_data.user_data.ge.*' => 'nullable|string', // f ou m
            'pixel_data.user_data.db' => 'nullable|array',
            'pixel_data.user_data.db.*' => 'nullable|string', // data do aniversario formato YYYYMMDD
            'pixel_data.user_data.st' => 'nullable|array',
            'pixel_data.user_data.st.*' => 'nullable|string', // estado com duas siglas sp, mg ..
            'pixel_data.custom_data' => 'nullable|array',
            'pixel_data.custom_data.currency' => 'nullable|string|size:3', // ISO 4217
            'pixel_data.custom_data.value' => 'nullable|numeric|min:0',
            'pixel_data.custom_data.contents' => 'nullable|array',
            'pixel_data.custom_data.contents.*' => 'nullable|array',
            'pixel_data.custom_data.contents.*.id' => 'nullable|string|max:255',
            'pixel_data.custom_data.contents.*.quantity' => 'nullable|integer|min:1',
            'pixel_data.custom_data.contents.*.delivery_category' => 'nullable|string|in:home_delivery,pickup,other', // Ajuste conforme necessário
        ];
    }
}
