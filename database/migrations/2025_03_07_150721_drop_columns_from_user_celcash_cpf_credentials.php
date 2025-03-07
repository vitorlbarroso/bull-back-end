<?php

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
        Schema::table('user_celcash_cpf_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'galax_pay_id',
                'api_auth_galax_id',
                'api_auth_galax_hash',
                'api_auth_public_token',
                'api_auth_confirm_hash_webhook',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_celcash_cpf_credentials', function (Blueprint $table) {
            $table->string('galax_pay_id')->nullable();
            $table->string('api_auth_galax_id')->nullable();
            $table->string('api_auth_galax_hash')->nullable();
            $table->string('api_auth_public_token')->nullable();
            $table->string('api_auth_confirm_hash_webhook')->nullable();
        });
    }
};
