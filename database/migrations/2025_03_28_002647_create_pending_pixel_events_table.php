<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pending_pixel_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->references('id')->on('products_offerings');;
            $table->string('payment_id')->references('galax_pay_id')->on('celcash_payments');
            $table->string('event_name')->default('Purchase');
            $table->json('payload');
            $table->string('status')->default('Waiting Payment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_pixel_events');
    }
};
