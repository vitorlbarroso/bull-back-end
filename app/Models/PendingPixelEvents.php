<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingPixelEvents extends Model
{
    protected $casts = [
        'payload' => 'array',
    ];

    public function productsOffering(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class, 'products_offering_id');
    }

    public function offerPixels()
    {
        return $this->hasMany(OfferPixel::class, 'product_offering_id', 'offer_id');
    }

    public function pixels(): BelongsTo
    {
        return $this->belongsTo(pixels::class);
    }

    public function celcashPayments(): BelongsTo
    {
        return $this->belongsTo(CelcashPayments::class, 'galax_pay_id');
    }
}
