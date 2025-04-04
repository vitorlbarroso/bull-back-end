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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('pix_tax_value')->default(4.99)->nullable()->change();
            $table->decimal('pix_money_tax_value')->default(1.50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('pix_tax_value')->default(4.49)->nullable()->change();
            $table->decimal('pix_money_tax_value')->default(2.99)->nullable()->change();
        });
    }
};
