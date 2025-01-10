<?php

namespace App\Http\Requests\Timer;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TimerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'timer_title' => ['required'],
            'timer_title_color' => ['required'],
            'timer_icon_color' => ['required'],
            'timer_background_color' => ['required'],
            'timer_progressbar_bg_color' => ['required'],
            'timer_progressbar_color' => ['required'],
            'countdown' => ['required', 'date'],
            'location' => ['nullable'],
            'position' => ['nullable'],
            'display' => ['required', 'boolean'],
            'media_id' => ['nullable', 'exists:media'],
            'end_timer_title' => ['nullable'],
            'is_fixed' => ['required','boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000', 400));
    }

    public function messages()
    {
        return [
            'timer_title.required' => 'O Texto do timer é obrigatório e não foi preenchido',
            'timer_title_color.required' => 'A cor do texto do timer é obrigatório e não foi preenchido',
            'timer_icon_color.email' => 'A cor do icone do Timer é obrigatório ser enviado',
            'description.required' => 'A descrição do produto é obrigatória e não foi preenchida',
            'product_type.required' => 'o tipo é obrigatorio e não foi enviado',
            'product_category.required' => 'A categoria não foi enviada',
            'whatsapp_support.required' => 'O telefone de whatsapp não foi preenchido',
            'card_description' => 'O nome da descrição no cartao de crédito não foi preenchido',
            'refund_time' => 'O campo de reembolso não foi preenchido',
        ];
    }
}
