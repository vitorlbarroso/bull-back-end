<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Enums\MediaTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->enum('media_type', MediaTypeEnum::getValues())->nullable()->after('file_type');
            $table->unsignedBigInteger('members_area_id')->nullable()->after('user_id');
            $table->foreign('members_area_id')->references('id')->on('members_area')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['members_area_id']);
            $table->dropColumn('members_area_id');
            $table->dropColumn('media_type');
        });
    }
};
