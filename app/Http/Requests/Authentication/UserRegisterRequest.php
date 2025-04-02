<?php

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Helpers\Responses;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UserRegisterRequest extends FormRequest
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
            'name' => 'required|string',
            'email' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:6',
            ]
        ];
    }

    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }

    public function messages(){
        return [
            'email.required' => 'email é obrigatório e não foi preenchido',
            'email.email' => 'O email deve ser um email válido',
            'password.required' => 'A senha é obrigatória e não foi preenchida',
            'password.min' => 'A senha precisa ter pelo menos 6 caracteres',
        ];
    }
}
