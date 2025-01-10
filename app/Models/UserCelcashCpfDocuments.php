<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCelcashCpfDocuments extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_cpf_credentials_id',
        'mother_name',
        'birth_date',
        'monthly_income',
        'about',
        'social_media_link',
        'cnh',
        'cnh_selfie',
        'cnh_picture',
        'cnh_address',
        'rg',
        'rg_address',
        'rg_front',
        'rg_back',
        'rg_address',
        'document_status',
        'document_refused_reason',
    ];

    public function credentials() : BelongsTo
    {
        return $this->belongsTo(UserCelcashCpfCredentials::class, 'user_cpf_credentials_id');
    }
}
