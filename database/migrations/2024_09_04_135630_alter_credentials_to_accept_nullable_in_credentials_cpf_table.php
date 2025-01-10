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
            $table->string('api_auth_galax_id')->nullable()->change();
            $table->string('api_auth_galax_hash')->nullable()->change();
            $table->string('api_auth_public_token')->nullable()->change();
            $table->string('api_auth_confirm_hash_webhook')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_celcash_cpf_credentials', function (Blueprint $table) {
            $table->string('api_auth_galax_id')->nullable(false);
            $table->string('api_auth_galax_hash')->nullable(false);
            $table->string('api_auth_public_token')->nullable(false);
            $table->string('api_auth_confirm_hash_webhook')->nullable(false);
        });
    }
};
