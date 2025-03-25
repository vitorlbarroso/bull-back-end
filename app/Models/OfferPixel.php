<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferPixel extends Model
{
    use SoftDeletes;

    protected $casts = [
        'status' => 'boolean',
    ];

    public function pixels(): BelongsTo
    {
        return $this->belongsTo(pixels::class);
    }
}
