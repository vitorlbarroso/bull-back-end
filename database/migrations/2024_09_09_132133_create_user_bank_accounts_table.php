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
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('banks_codes_id')->nullable()->constrained('banks_codes')->nullOnDelete();
            $table->string('responsible_name');
            $table->string('responsible_document');
            $table->enum('account_type', ['current', 'savings']);
            $table->string('account_number');
            $table->string('account_agency');
            $table->string('account_check_digit')->nullable();
            $table->enum('pix_type_key', ['cpf', 'cnpj', 'email', 'mobile_phone', 'random'])->nullable();
            $table->string('pix_key')->nullable();
            $table->enum('status', ['approved', 'analysis', 'reproved'])->default('analysis');
            $table->string('reproved_reason')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
    }
};
