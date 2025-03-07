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
        'cnh',
        'cnh_selfie',
        'cnh_picture',
        'cnh_address',
        'rg',
        'rg_address_media',
        'rg_front_media',
        'rg_back_media',
        'document_status',
        'document_refused_reason',
    ];

    public function credentials() : BelongsTo
    {
        return $this->belongsTo(UserCelcashCpfCredentials::class, 'user_cpf_credentials_id');
    }

    public function rg_front() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_front');
    }

    public function rg_back() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_back');
    }

    public function rg_selfie() : BelongsTo
    {
        return $this->belongsTo(Media::class, 'rg_selfie');
    }
}
