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
        Schema::table('celcash_payments_gateway_data', function (Blueprint $table) {
            $table->longText('token')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments_gateway_data', function (Blueprint $table) {
            $table->string('token', 255)->change();
        });
    }
};
