<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogsPixelEvent extends Model
{
    protected $fillable = [
        'payload',
        'TID',
        'event_name',
        'product_offering_id',
        'status',
        'error',
    ];
    protected $casts = [
        'payload' => 'array',
    ];
}
