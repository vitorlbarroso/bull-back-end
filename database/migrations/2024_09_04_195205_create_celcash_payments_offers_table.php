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
        Schema::create('celcash_payments_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celcash_payments_id')->nullable()->constrained('celcash_payments')->nullOnDelete();
            $table->foreignId('products_offerings_id')->nullable()->constrained('products_offerings')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celcash_payments_offers');
    }
};
