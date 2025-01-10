<?php

namespace App\Http\Requests\Orderbump;

use Illuminate\Foundation\Http\FormRequest;

class OrderBumpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checkout_id' => 'required|integer',
            'products_offerings' => 'required|array',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
