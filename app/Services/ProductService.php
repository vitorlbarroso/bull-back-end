<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductService
{

    public function updateProduct(Product $product, array $data)
    {

        if (isset($data['product_type'])) {
            $data['product_types_id'] = $data['product_type'];
            unset($data['product_type']);
        }
        if (isset($data['product_category'])) {
            $data['product_categories_id'] = $data['product_category'];
            unset($data['product_category']);
        }
        $product->update($data);

        $data['id'] = $product->id;

        return $data;
    }

    public function softDeleteProduct($getProduct)
    {

        $getProduct->update([
            'is_deleted' => 1
        ]);

        return $getProduct;
    }
}


class ProductUpdateException extends \Exception
{
    // Custom exception for product update errors
}
