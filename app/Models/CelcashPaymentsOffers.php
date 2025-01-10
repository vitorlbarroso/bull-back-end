<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelcashPaymentsOffers extends Model
{
    use HasFactory;

    protected $fillable = [
        'celcash_payments_id',
        'products_offerings_id',
        'type'
    ];

    public function payment() : BelongsTo
    {
        return $this->belongsTo(CelcashPayments::class, 'celcash_payments_id');
    }

    public function offer() : BelongsTo
    {
        return $this->belongsTo(ProductOffering::class, 'products_offerings_id');
    }
}
