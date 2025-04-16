<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CelcashPayments extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiver_user_id',
        'buyer_user_id',
        'galax_pay_id',
        'type',
        'installments',
        'total_value',
        'value_to_receiver',
        'value_to_platform',
        'payday',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_document_cpf',
        'description',
        'status',
        'reason_denied',
        'adquirer',
        'buyer_zipcode',
        'buyer_state',
        'buyer_city',
        'buyer_number',
        'buyer_complement',
        'src',
        'sck',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'utm_content',
        'utm_term',
    ];

    public function receiver_user_id() : BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function buyer_user_id() : BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function pix_data() : HasOne
    {
        return $this->hasOne(CelcashPaymentsPixData::class, 'celcash_payments_id');
    }

    public function card_data() : HasOne
    {
        return $this->hasOne(CelcashPaymentsCardData::class, 'celcash_payments_id');
    }

    public function billet_data() : HasOne
    {
        return $this->hasOne(CelcashPaymentsBilletData::class, 'celcash_payments_id');
    }

    public function payment_offers() : HasMany
    {
        return $this->hasMany(CelcashPaymentsOffers::class, 'celcash_payments_id');
    }
}
