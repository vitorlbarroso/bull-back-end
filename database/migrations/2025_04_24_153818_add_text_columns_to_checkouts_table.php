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
        Schema::table('checkouts', function (Blueprint $table) {
            $table->string('text')->nullable();
            $table->boolean('text_display')->default(false);
            $table->string('text_font_color')->default('#FFFFFF');
            $table->string('text_bg_color')->default('#000000');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropColumn('text');
            $table->dropColumn('text_display');
            $table->dropColumn('text_font_color');
            $table->dropColumn('text_bg_color');
        });
    }
};
