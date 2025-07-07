<?php

namespace App\Http\Requests\Checkout;

use App\Http\Helpers\Responses;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatecheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'checkout_title' => 'string|max:255',
            'product_offering_id' => 'required|integer',
            'exit_popup' => 'nullable',
            'status' => 'nullable',
            'order_bump_title' => 'required|string|max:255',
            'orders' => 'array',
            'timer.id' => 'integer',
            'timer.is_fixed' => 'boolean',
            'timer.countdown' => 'date_format:H:i:s',
            'timer.display' => 'boolean',
            'timer.end_timer_title' => 'string|max:255',
            'timer.timer_title' => 'string|max:255',
            'timer.timer_title_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'timer.timer_bg_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'timer.timer_icon_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'timer.timer_progressbar_bg_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'timer.timer_progressbar_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'banner.display' => 'boolean',
            'banner.id' => 'nullable|integer',
            'configs.background_color' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'checkout_style' => 'integer',
            'is_active_contact_and_documents_fields' => 'boolean',
            'is_active_address_fields' => 'boolean',
            'fixed_values_fields' => 'boolean',
            'configs.back_redirect_url' => 'nullable|string',
            'configs.elements_color' => 'nullable|string',
            'text.text' => 'nullable|string',
            'text.text_display' => 'boolean',
            'text.text_font_color' => 'string',
            'text.text_bg_color' => 'string',
            'warning_pix' => 'boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function failedValidation(Validator $validator) {
        throw new HttpResponseException(Responses::ERROR('Campos obrigatórios não preenchidos ou inválidos', $validator->errors(), '-1000'));

    }

    public function messages(){

        return [
            'timer.countdown' => 'O contador informado não é válido',
            'product_offering_id' => 'O id da oferta não foi informado'
        ];

    }
}
