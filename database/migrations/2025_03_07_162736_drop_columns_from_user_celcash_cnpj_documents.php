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
        Schema::table('user_celcash_cnpj_documents', function (Blueprint $table) {
            $table->dropColumn(['monthly_income', 'about', 'social_media_link', 'rg_selfie', 'rg_front', 'rg_back']);
        });

        Schema::table('user_celcash_cnpj_documents', function (Blueprint $table) {
            $table->foreignId('rg_address_media')->nullable()->constrained('media')->cascadeOnDelete();
            $table->foreignId('rg_front_media')->nullable()->constrained('media')->cascadeOnDelete();
            $table->foreignId('rg_back_media')->nullable()->constrained('media')->cascadeOnDelete();
            $table->foreignId('company_document_media')->nullable()->constrained('media')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_celcash_cnpj_documents', function (Blueprint $table) {
            $table->dropForeign(['rg_address_media_id']);
            $table->dropForeign(['rg_front_media_id']);
            $table->dropForeign(['rg_back_media_id']);

            $table->dropColumn(['rg_address_media_id', 'rg_front_media_id', 'rg_back_media_id']);

            $table->string('monthly_income')->nullable();
            $table->text('about')->nullable();
            $table->string('social_media_link')->nullable();
            $table->string('rg_address')->nullable();
            $table->string('rg_front')->nullable();
            $table->string('rg_back')->nullable();
        });
    }
};
