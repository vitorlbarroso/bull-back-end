<?php

namespace App\Http\Requests\Banner;

use Illuminate\Foundation\Http\FormRequest;

class CreateBannerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'location' => ['nullable'],
            'position' => ['nullable'],
            'display' => ['boolean'],
            'media_id' => ['required', 'exists:media'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
