<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_withdraw_period',
        'default_withdraw_tax',
        'default_pix_tax_value',
        'default_pix_money_tax_value',
        'default_card_tax_value',
        'default_card_money_tax_value',
        'default_cash_in_adquirer',
        'default_cash_out_adquirer',
        'admin_access_token',
    ];

    protected $hidden = [
        'admin_access_token'
    ];
}
