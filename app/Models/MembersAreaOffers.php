<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembersAreaOffers extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'members_area_id',
        'product_offering_id',
    ];

    public function membersArea()
    {
        return $this->belongsTo(MembersArea::class, 'members_area_id');
    }

    public function productOffering(): BelongsTo
    {
        return $this->belongsTo(ProductOffering::class);
    }
}
