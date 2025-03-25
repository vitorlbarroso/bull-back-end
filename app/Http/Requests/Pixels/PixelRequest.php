<?php

namespace App\Http\Requests\Pixels;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PixelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pixels_id' => 'integer',
            'product_offering_id' => 'integer',
            'access_token' => 'nullable|string',
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
