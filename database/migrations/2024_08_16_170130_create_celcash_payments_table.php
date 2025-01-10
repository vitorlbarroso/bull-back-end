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
        Schema::create('celcash_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('galax_pay_id');
            $table->enum('type', ['pix', 'billet', 'card']);
            $table->integer('installments');
            $table->integer('total_value');
            $table->integer('value_to_receiver');
            $table->integer('value_to_platform');
            $table->string('payday');
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_document_cpf');
            $table->string('description')->nullable();
            $table->enum('status', ['waiting_payment', 'not_send', 'authorized', 'captured', 'denied', 'reversed', 'chargeback', 'pending_pix', 'payed_pix', 'unavailable_pix']);
            $table->string('reason_denied')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celcash_payments');
    }
};
