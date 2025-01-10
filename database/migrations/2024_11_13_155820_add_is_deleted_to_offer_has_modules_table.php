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
        Schema::table('offer_has_modules', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0)->after('is_selected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offer_has_modules', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
