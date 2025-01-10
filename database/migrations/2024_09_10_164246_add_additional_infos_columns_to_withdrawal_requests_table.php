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
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->string('galax_pay_id')->nullable();
            $table->integer('tax_value')->nullable();
            $table->string('central_bank_unic_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn('galax_pay_id');
            $table->dropColumn('tax_value');
            $table->dropColumn('central_bank_unic_id');
        });
    }
};
