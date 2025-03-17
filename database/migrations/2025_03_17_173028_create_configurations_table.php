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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->integer('default_withdraw_period')->nullable();
            $table->decimal('default_withdraw_tax')->nullable();
            $table->decimal('default_pix_tax_value')->nullable();
            $table->decimal('default_pix_money_tax_value')->nullable();
            $table->decimal('default_card_tax_value')->nullable();
            $table->decimal('default_card_money_tax_value')->nullable();
            $table->string('default_cash_in_adquirer')->nullable();
            $table->string('default_cash_out_adquirer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
