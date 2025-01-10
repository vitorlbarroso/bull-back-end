<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'banks_codes_id',
        'responsible_name',
        'responsible_document',
        'account_type',
        'account_number',
        'account_agency',
        'account_check_digit',
        'pix_type_key',
        'pix_key',
        'status',
        'reproved_reason',
        'is_active',
        'is_deleted',
    ];

    public function banks_code() : BelongsTo
    {
        return $this->belongsTo(BanksCode::class, 'banks_codes_id');
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequests::class, 'user_bank_account_id');
    }
}
