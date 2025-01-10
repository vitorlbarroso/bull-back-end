<?php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserBankAccountRequest extends FormRequest
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
            'banks_codes_id' => 'required|integer',
            'responsible_name' => 'required|string',
            'responsible_document' => 'required|string',
            'account_type' => 'required|string',
            'account_number' => 'required|string',
            'account_agency' => 'required|string',
            'account_check_digit' => '',
            'pix_type_key' => 'required|string',
            'pix_key' => 'required|string',
        ];
    }
}
