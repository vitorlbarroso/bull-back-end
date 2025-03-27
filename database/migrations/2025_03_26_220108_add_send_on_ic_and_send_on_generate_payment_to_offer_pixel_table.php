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
        Schema::table('offer_pixels', function (Blueprint $table) {
            $table->boolean('send_on_ic')->default(true)->after('status');
            $table->boolean('send_on_generate_payment')->default(false)->after('send_on_ic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_pixels', function (Blueprint $table) {
            $table->dropColumn('send_on_ic');
            $table->dropColumn('send_on_generate_payment');
        });
    }
};
