<?php

namespace App\Http\Requests\Pixels;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PixelSendRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event_name' => 'required|string|max:255',
            'event_source_url' => 'required|url',
            'event_time' => 'required|integer',
            'action_source' => 'required|string|in:website,app,other', // Ajuste conforme o contexto
            'user_data' => 'required|array',
            'user_data.em' => 'required|array',
            'user_data.em.*' => 'required|string', // Hash de 64 caracteres
            'user_data.ph' => 'required|array',
            'user_data.ph.*' => 'required|string', // formato 5511985236631
            'user_data.ct' => 'required|array',
            'user_data.ct.*' => 'required|string',
            'user_data.client_ip_address' => 'required|ip',
            'user_data.client_user_agent' => 'required|string|max:255',
            'user_data.fbc' => 'nullable|string', // identificação do clique no Facebook está armazenado no cookie
            'user_data.fbp' => 'nullable|string', // identificação do navegador no Facebook está armazenado no cookie
            'user_data.fn' => 'nullable|array',
            'user_data.fn.*' => 'nullable|string',
            'user_data.country' => 'nullable|array',
            'user_data.country.*' => 'nullable|string',
            'user_data.zp' => 'nullable|array',
            'user_data.zp.*' => 'nullable|string',
            'user_data.ge' => 'nullable|array',
            'user_data.ge.*' => 'nullable|string', // f ou m
            'user_data.db' => 'nullable|array',
            'user_data.db.*' => 'nullable|string', // data do aniversario formato YYYYMMDD
            'user_data.st' => 'nullable|array',
            'user_data.st.*' => 'nullable|string', // estado com duas siglas sp, mg ..
            'custom_data' => 'required|array',
            'custom_data.currency' => 'required|string|size:3', // ISO 4217
            'custom_data.value' => 'required|numeric|min:0',
            'custom_data.contents' => 'required|array',
            'custom_data.contents.*' => 'required|array',
            'custom_data.contents.*.id' => 'required|string|max:255',
            'custom_data.contents.*.quantity' => 'required|integer|min:1',
            'custom_data.contents.*.delivery_category' => 'nullable|string|in:home_delivery,pickup,other', // Ajuste conforme necessário
        ];
    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }
    public function authorize(): bool
    {
        return true;
    }
}
