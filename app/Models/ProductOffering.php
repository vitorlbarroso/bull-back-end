<?php

namespace App\Models;

use App\Enums\OfferChargeTypeEnum;
use App\Enums\OfferTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOffering extends Model
{
    use SoftDeletes;

    protected $table = 'products_offerings';

    protected $fillable = [
        'offer_name',
        'product_id',
        'description',
        'fake_price',
        'price',
        'type',
        'charge_type',
        'recurrently_installments',
        'is_deleted',
        'enable_billet',
        'enable_card',
        'enable_pix',
        'deleted_at',
        'offer_type',
        'sale_completed_page_url'
    ];

    protected $casts = [
        'offer_type' => OfferTypeEnum::class,
        'charge_type' => OfferChargeTypeEnum::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class, 'product_offering_id');
    }

    public function order_bumps(): HasMany
    {
        return $this->hasMany(OrderBump::class, 'products_offerings_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CelcashPaymentsOffers::class, 'products_offerings_id');
    }

    public function getFakePriceAttribute($value)
    {
        return (float) $value;
    }

    public function getPriceAttribute($value)
    {
        return (float) $value;
    }

    public function getIsDeletedAttribute($value)
    {
        return (boolean) $value;
    }

    public function offerPixels()
    {
        return $this->hasMany(OfferPixel::class, 'product_offering_id');
    }
}
