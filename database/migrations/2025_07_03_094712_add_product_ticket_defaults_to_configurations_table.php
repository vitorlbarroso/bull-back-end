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
        Schema::table('configurations', function (Blueprint $table) {
            $table->decimal('default_min_product_ticket', 8, 2)->default(5.00);
            $table->decimal('default_max_product_ticket', 8, 2)->default(297.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['default_min_product_ticket', 'default_max_product_ticket']);
        });
    }
};
