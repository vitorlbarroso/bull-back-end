<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelcashWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_type',
        'webhook_title',
        'webhook_id',
        'webhook_event',
        'webhook_sender',
        'webhook_data',
    ];
}
