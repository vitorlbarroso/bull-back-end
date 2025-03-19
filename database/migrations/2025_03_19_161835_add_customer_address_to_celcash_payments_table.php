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
            $table->string('buyer_zipcode')->nullable();
            $table->string('buyer_state')->nullable();
            $table->string('buyer_city')->nullable();
            $table->integer('buyer_number')->nullable();
            $table->string('buyer_complement')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->dropColumn('buyer_zipcode');
            $table->dropColumn('buyer_state');
            $table->dropColumn('buyer_city');
            $table->dropColumn('buyer_number');
            $table->dropColumn('buyer_complement');
        });
    }
};
