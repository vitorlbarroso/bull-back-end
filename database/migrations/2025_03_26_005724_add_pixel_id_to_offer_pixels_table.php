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
            $table->string('pixel')->after('pixels_id');
            $table->foreign('pixels_id')->references('id')->on('pixels')->onDelete('cascade');  // Relacionamento com a tabela pixels
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_pixels', function (Blueprint $table) {
            $table->dropColumn('pixel');
        });
    }
};
