<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserAccountTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_media_id',
        'account_type',
        'new_sales_notifications',
        'name_products_sales_notifications',
        'price_products_sales_notifications',
        'refused_products_sales_notifications',
        'new_withdraw_notifications',
        'last_login',
        'tax_value',
        'is_blocked',
        'withdrawal_period',
        'withdrawal_tax',
        'pix_tax_value',
        'pix_money_tax_value',
        'card_tax_value',
        'card_money_tax_value',
        'cash_in_adquirer_name',
        'cash_out_adquirer_name',
        'auto_withdrawal',
        'min_product_ticket',
        'max_product_ticket',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'account_type' => UserAccountTypeEnum::class,
    ];

    public function profile_media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'profile_media_id');
    }

    public function medias(): BelongsToMany
    {
        return $this->belongsToMany(Media::class);
    }

    public function forgot_password_tokens(): HasMany
    {
        return $this->hasMany(ForgotPassword::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function user_cpf_credentials() : HasOne
    {
        return $this->hasOne(UserCelcashCpfCredentials::class, 'user_id');
    }

    public function user_cnpj_credentials() : HasOne
    {
        return $this->hasOne(UserCelcashCnpjCredentials::class, 'user_id');
    }

    public function payments_receiver() : HasMany
    {
        return $this->hasMany(CelcashPayments::class, 'receiver_user_id');
    }

    public function payments_buyer() : HasMany
    {
        return $this->hasMany(CelcashPayments::class, 'buyer_user_id');
    }

    public function account_bank() : HasMany
    {
        return $this->hasMany(UserBankAccount::class, 'user_id');
    }

    public function withdrawals() : HasMany
    {
        return $this->hasMany(WithdrawalRequests::class, 'user_id');
    }
}
