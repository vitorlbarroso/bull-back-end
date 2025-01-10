<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkout extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_offering_id',
        'checkout_hash',
        'is_active',
        'order_bump_title',
        'checkout_title',
        'background_color',
        'whatsapp_is_active',
        'whatsapp_number',
        'exit_popup',
        'whatsapp_message',
        'banner_id',
        'banner_display',
        'timer_id',
        'is_deleted',
        'deleted_at',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class, 'product_offering_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'banner_id');
    }

    public function timer(): BelongsTo
    {
        return $this->belongsTo(Timer::class);
    }

    public function order_bumps(): HasMany
    {
        return $this->hasMany(OrderBump::class, 'checkout_id');
    }

    public function getBannerDisplayAttribute($value)
    {
        return (bool) $value;
    }
}
