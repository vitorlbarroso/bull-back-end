<?php

namespace App\Http\Requests\Product;

use App\Http\Helpers\Responses;
use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class BaseProductRequest extends FormRequest
{

    protected $validatedProduct;
    public function authorize()
    {
        return true; // Ajuste conforme necessário
    }

    public function commonRules()
    {
        return [
            'product_name' => 'string|min:3|nullable',
            'product_description' => 'string|min:3|nullable',
            'product_type' => 'integer|min:1|nullable',
            'product_category' => 'integer|min:1|nullable',
            'card_description' => 'string|min:1|nullable',
            'email_support' => 'string|min:10|nullable',
            'whatsapp_support' => 'string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|nullable',
            'media_id' => 'integer|nullable',
            'refund_time' => 'integer|nullable',
            'is_active' => 'boolean|nullable',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $productId = $this->route('id'); // Validando product_id como query param
            $product = Product::where('id', $productId)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$product) {
                $validator->errors()->add('product_id', 'O produto não existe ou não pertence ao usuário autenticado.');
            } elseif ($product->is_blocked || $product->is_deleted) {
                $validator->errors()->add('product_id', 'O produto está bloqueado ou deletado.');
            }
            $this->validatedProduct = $product;
        });
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(Responses::ERROR('Não foi possível realizar essa ação', $validator->errors(), '-1000', 400));
    }

    public function validatedProduct()
    {
        return $this->validatedProduct;
    }
}
