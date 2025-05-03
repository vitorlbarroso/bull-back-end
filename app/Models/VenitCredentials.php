<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenitCredentials extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_key',
        'secret_key'
    ];
}
