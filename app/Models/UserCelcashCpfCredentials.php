<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserCelcashCpfCredentials extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'document',
        'phone',
        'email',
        'soft_descriptor',
        'address_zipcode',
        'address_street',
        'address_number',
        'address_neighborhood',
        'address_city',
        'address_state',
        'is_active',
        'is_blocked',
        'blocked_reason',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents() : HasOne
    {
        return $this->hasOne(UserCelcashCpfDocuments::class, 'user_cpf_credentials_id');
    }
}
