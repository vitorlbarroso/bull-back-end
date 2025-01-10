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
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->enum('status', ['waiting_payment', 'not_send', 'authorized', 'captured', 'denied', 'reversed', 'chargeback', 'pending_pix', 'payed_pix', 'unavailable_pix', 'refunded'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->enum('status', ['waiting_payment', 'not_send', 'authorized', 'captured', 'denied', 'reversed', 'chargeback', 'pending_pix', 'payed_pix', 'unavailable_pix'])->change();
        });
    }
};
