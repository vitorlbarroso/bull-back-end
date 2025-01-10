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
        Schema::create('user_celcash_cpf_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_cpf_credentials_id')->constrained('user_celcash_cpf_credentials')->cascadeOnDelete();
            $table->string('mother_name');
            $table->string('birth_date');
            $table->string('monthly_income');
            $table->string('about');
            $table->string('social_media_link');
            $table->enum('cnh', ['not_send', 'send']);
            $table->text('cnh_selfie')->nullable();
            $table->text('cnh_picture')->nullable();
            $table->text('cnh_address')->nullable();
            $table->enum('rg', ['not_send', 'send']);
            $table->text('rg_address')->nullable();
            $table->text('rg_front')->nullable();
            $table->text('rg_back')->nullable();
            $table->enum('document_status', ['approved', 'denied', 'pending', 'analyzing', 'empty']);
            $table->string('document_refused_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_celcash_cpf_documents');
    }
};
