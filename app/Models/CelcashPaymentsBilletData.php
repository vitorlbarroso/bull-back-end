<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelcashPaymentsBilletData extends Model
{
    use HasFactory;

    protected $fillable = [
        'celcash_payments_id',
        'pdf',
        'bank_line',
        'bank_agency',
        'bank_account',
    ];

    public function payment() : BelongsTo
    {
        return $this->belongsTo(CelcashPayments::class, 'celcash_payments_id');
    }
}
