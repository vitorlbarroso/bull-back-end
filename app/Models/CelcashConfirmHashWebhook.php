<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelcashConfirmHashWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_event',
        'confirm_hash',
    ];
}
