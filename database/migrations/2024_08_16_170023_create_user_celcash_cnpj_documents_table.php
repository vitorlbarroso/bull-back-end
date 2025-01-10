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
        Schema::create('user_celcash_cnpj_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_cnpj_credentials_id')->constrained('user_celcash_cnpj_credentials')->cascadeOnDelete();
            $table->integer('monthly_income');
            $table->string('about');
            $table->string('social_media_link');
            $table->string('responsible_document_cpf');
            $table->string('responsible_name');
            $table->string('mother_name');
            $table->string('birth_date');
            $table->enum('type', ['partner', 'attorney', 'personinvolved']);
            $table->text('company_document');
            $table->enum('cnh', ['not_send', 'send']);
            $table->text('cnh_selfie')->nullable();
            $table->text('cnh_picture')->nullable();
            $table->enum('rg', ['not_send', 'send']);
            $table->text('rg_selfie')->nullable();
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
        Schema::dropIfExists('user_celcash_cnpj_documents');
    }
};
