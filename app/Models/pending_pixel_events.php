<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class pending_pixel_events extends Model
{
    protected $casts = [
        'payload' => 'array',
    ];

    public function productsOffering(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class, 'products_offering_id');
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
