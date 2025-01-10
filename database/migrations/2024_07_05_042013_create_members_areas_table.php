<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('area_name');
            $table->string('area_type');
            $table->string('slug')->nullable();
            $table->boolean('comments_allow')->default(true);;
            $table->boolean('is_comments_auto_approve')->default(true);;
            $table->string('layout_type');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();;
            $table->string('blocked_at')->nullable();;
            $table->string('blocked_reason')->nullable();;
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members_area');
    }
};
