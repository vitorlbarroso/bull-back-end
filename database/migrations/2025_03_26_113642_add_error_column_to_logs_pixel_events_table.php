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
        Schema::table('logs_pixel_events', function (Blueprint $table) {
            $table->text('error')->nullable()->after('status'); // Substitua 'existing_column' pelo nome de uma coluna existente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs_pixel_events', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};
