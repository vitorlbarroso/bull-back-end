<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediusToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_type',
        'token_value',
    ];
}
