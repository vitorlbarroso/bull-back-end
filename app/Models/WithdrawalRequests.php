<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequests extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_bank_accounts_id',
        'withdrawal_amount',
        'status',
        'rejected_reason',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account_bank() : BelongsTo
    {
        return $this->belongsTo(UserBankAccount::class, 'user_bank_accounts_id');
    }
}
