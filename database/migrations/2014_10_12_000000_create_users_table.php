<?php

use App\Enums\UserAccountTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('account_type')->default(UserAccountTypeEnum::PF->value);
            $table->boolean('new_sales_notifications')->default(true);
            $table->boolean('name_products_sales_notifications')->default(false);
            $table->boolean('price_products_sales_notifications')->default(true);
            $table->boolean('refused_products_sales_notifications')->default(false);
            $table->boolean('new_withdraw_notifications')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
