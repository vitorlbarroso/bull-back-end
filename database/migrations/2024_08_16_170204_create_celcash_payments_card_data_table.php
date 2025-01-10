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
        Schema::create('celcash_payments_card_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celcash_payments_id')->constrained('celcash_payments')->cascadeOnDelete();
            $table->string('number');
            $table->string('brand_name');
            $table->string('card_expirest_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celcash_payments_card_data');
    }
};
