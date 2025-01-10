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
        Schema::table('user_celcash_cnpj_credentials', function (Blueprint $table) {
            $table->string('galax_pay_id')->after('address_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_celcash_cnpj_credentials', function (Blueprint $table) {
            $table->dropColumn('galax_pay_id');
        });
    }
};
