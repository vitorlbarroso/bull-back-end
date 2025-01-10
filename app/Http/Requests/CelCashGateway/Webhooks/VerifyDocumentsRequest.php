<?php

namespace App\Http\Requests\CelCashGateway\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentsRequest extends FormRequest
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
            'event' => 'required|string',
            'confirmHash' => 'required|string',
            'Company' => 'required',
        ];
    }
}
