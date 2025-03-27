<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferPixel extends Model
{
    use SoftDeletes;

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
