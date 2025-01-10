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
        Schema::create('celcash_webhooks', function (Blueprint $table) {
            $table->id();
            $table->enum('webhook_type', ['crash', 'normal'])->default('normal');
            $table->string('webhook_title')->nullable();
            $table->integer('webhook_id')->nullable();
            $table->string('webhook_event')->nullable();
            $table->string('webhook_sender')->nullable();
            $table->longText('webhook_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celcash_webhooks');
    }
};
