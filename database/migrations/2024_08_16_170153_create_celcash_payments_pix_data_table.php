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
        Schema::create('celcash_payments_pix_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celcash_payments_id')->constrained('celcash_payments')->cascadeOnDelete();
            $table->text('qr_code')->nullable();
            $table->text('reference')->nullable();
            $table->text('image')->nullable();
            $table->text('page')->nullable();
            $table->string('expires_in')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celcash_payments_pix_data');
    }
};
