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
        'rg_selfie_media',
        'rg_front_media',
        'rg_back_media',
        'company_document_media',
    ];

    public function credentials() : BelongsTo
    {
        return $this->belongsTo(UserCelcashCnpjCredentials::class, 'users_cnpj_credentials_id');
    }

    public function rg_front() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_front_media');
    }

    public function rg_back() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_back_media');
    }

    public function rg_selfie() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_selfie_media');
    }

    public function company_document() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'company_document_media');
    }
}
