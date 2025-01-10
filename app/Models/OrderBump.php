<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderBump extends Model
{
    protected $fillable = [
        'position',
        'checkout_id',
        'products_offerings_id',
        'is_deleted',
        'deleted_at',
    ];

    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class, 'products_offerings_id');
    }
}
