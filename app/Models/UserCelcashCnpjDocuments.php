<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCelcashCnpjDocuments extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_cnpj_credentials_id',
        'monthly_income',
        'about',
        'social_media_link',
        'responsible_document_cpf',
        'responsible_name',
        'mother_name',
        'birth_date',
        'type',
        'company_document',
        'cnh',
        'cnh_selfie',
        'cnh_picture',
        'rg',
        'rg_selfie',
        'rg_front',
        'rg_back',
    ];

    public function credentials() : BelongsTo
    {
        return $this->belongsTo(UserCelcashCnpjCredentials::class, 'users_cnpj_credentials_id');
    }
}
