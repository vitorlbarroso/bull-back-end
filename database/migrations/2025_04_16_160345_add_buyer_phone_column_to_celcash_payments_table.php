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
            $table->string('buyer_phone')->nullable()->after('buyer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->dropColumn('buyer_phone');
        });
    }
};
