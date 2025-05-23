<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferPixel extends Model
{

    protected $fillable = [
        'pixels_id',
        'pixel',
        'access_token',
        'product_offering_id',
        'status',
        'send_on_ic',
        'send_on_generate_payment',
    ];

    protected $casts = [
        'status' => 'boolean',
        'send_on_ic' => 'boolean',
        'send_on_generate_payment' => 'boolean',
    ];

    public function pixels(): BelongsTo
    {
        return $this->belongsTo(pixels::class);
    }
    public function productOffering()
    {
        return $this->belongsTo(ProductOffering::class, 'product_offering_id');
    }
}
