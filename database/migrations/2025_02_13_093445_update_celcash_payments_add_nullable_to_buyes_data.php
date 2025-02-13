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
            $table->string('buyer_name')->nullable()->change();
            $table->string('buyer_email')->nullable()->change();
            $table->string('buyer_document_cpf')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->string('buyer_name')->change();
            $table->string('buyer_email')->change();
            $table->string('buyer_document_cpf')->change();
        });
    }
};
