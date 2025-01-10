<?php

namespace App\Http\Requests\Authentication;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DefineForgotPasswordRequest extends FormRequest
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
            'token' => 'required|string',
            'email' => 'required|string',
            'new_password' =>[
                'required',
                'string',
                'min:7',
                'regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/', // Pelo menos uma letra maiúscula e uma minúscula
            ]
        ];
    }

    public function failedValidation(Validator $validator) {

        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));

    }
    public function messages(){

        return [
            'new_password.min' => 'A senha deve ter pelo menos 7 caracteres.',
            'new_password.regex' => 'A senha deve conter pelo menos uma letra maiúscula e uma minúscula.', // Caso você esteja usando regex
        ];

    }
}
