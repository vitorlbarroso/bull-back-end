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
        Schema::table('members_area_offers', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0)->after('product_offering_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members_area_offers', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
