<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelcashPaymentsPixData extends Model
{
    use HasFactory;

    protected $fillable = [
        'celcash_payments_id',
        'qr_code',
        'reference',
        'image',
        'page',
        'expires_in',
    ];

    public function payment() : BelongsTo
    {
        return $this->belongsTo(CelcashPayments::class, 'celcash_payments_id');
    }
}
