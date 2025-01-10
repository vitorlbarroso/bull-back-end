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
        Schema::create('user_celcash_cnpj_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_cpf');
            $table->string('document_cnpj');
            $table->string('name_display');
            $table->string('phone');
            $table->string('email');
            $table->string('soft_descriptor')->default('CompraFlamePay');
            $table->string('cnae');
            $table->enum('type_company_cnpj', ['ltda', 'eireli', 'association', 'individual_entrepeneur', 'mei', 'sa', 'slu']);
            $table->string('address_zipcode');
            $table->string('address_street');
            $table->string('address_number');
            $table->string('address_neighborhood');
            $table->string('address_city');
            $table->string('address_state');
            $table->string('api_auth_galax_id');
            $table->string('api_auth_galax_hash');
            $table->string('api_auth_public_token');
            $table->string('api_auth_confirm_hash_webhook');
            $table->boolean('is_active')->default(1);
            $table->boolean('is_blocked')->default('0');
            $table->string('blocked_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_celcash_cnpj_credentials');
    }
};
