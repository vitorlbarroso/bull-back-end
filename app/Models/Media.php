<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_name',
        's3_name',
        's3_url',
        'file_type',
        'is_deleted',
    ];

    public function user_profile_media(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'user_id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'media_id');
    }

    public function checkout_banner(): HasOne
    {
        return $this->hasOne(Checkout::class, 'banner_id');
    }
}
