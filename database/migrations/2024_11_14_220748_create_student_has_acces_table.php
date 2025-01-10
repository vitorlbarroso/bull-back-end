<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_has_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('members_area_offers_id');
            $table->boolean('is_active');
            $table->boolean('is_deleted');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_has_access');
    }
};
