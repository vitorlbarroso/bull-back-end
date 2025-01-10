<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class studentHasAccess extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'members_area_offers_id',
        'is_active',
        'is_deleted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membersAreaOffers(): BelongsTo
    {
        return $this->belongsTo(MembersAreaOffers::class);
    }

}
