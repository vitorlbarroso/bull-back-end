<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelcashPaymentsCardData extends Model
{
    use HasFactory;

    protected $fillable = [
        'celcash_payments_id',
        'number',
        'brand_name',
        'card_expirest_at',
    ];

    public function payment() : BelongsTo
    {
        return $this->belongsTo(CelcashPayments::class, 'celcash_payments_id');
    }
}
