<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'timer_title',
        'timer_title_color',
        'timer_icon_color',
        'timer_bg_color',
        'timer_progressbar_bg_color',
        'timer_progressbar_color',
        'end_timer_title',
        'countdown',
        'display',
        'is_fixed',
        'is_deleted',
        'deleted_at',
    ];

    public function checkout(): HasOne
    {
        return $this->hasOne(Checkout::class, 'timer_id');
    }
}
