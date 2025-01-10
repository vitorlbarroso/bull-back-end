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
        Schema::table('members_area', function (Blueprint $table) {
            if (Schema::hasColumn('members_area', 'media_id')) {
                $table->dropForeign(['media_id']);
                $table->dropColumn('media_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('members_area', function (Blueprint $table) {
            $table->unsignedBigInteger('media_id')->nullable();
        });
    }
};
