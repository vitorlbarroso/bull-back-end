<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperCredentials extends Model
{
    use HasFactory;

    protected $fillable = [
        'credential_type',
        'credential_value',
    ];
}
