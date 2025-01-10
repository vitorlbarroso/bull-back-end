<?php

namespace App\Http\Requests\MembersArea;

use App\Http\Helpers\Responses;
use App\Models\MembersArea;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class DestroyMembersAreaRequest extends FormRequest
{
    protected $validatedArea;

    public function rules(): array
    {
        return [

        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $member_Area_Id = $this->route('id'); // Validando id como query param
            $getArea = MembersArea::where('id', $member_Area_Id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$getArea) {
                $validator->errors()->add('id', 'A área não existe ou não pertence ao usuário.');
            } elseif ($getArea->is_blocked || $getArea->is_deleted) {
                $validator->errors()->add('id', 'A Área está bloqueada ou deletada.');
            }
            $this->validatedArea = $getArea;
        });
    }

    public function validatedArea()
    {
        return $this->validatedArea;
    }

    public function authorize(): bool
    {
        return true;
    }
}
