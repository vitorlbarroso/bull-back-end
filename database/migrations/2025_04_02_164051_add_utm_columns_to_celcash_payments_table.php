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
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->string('src')->nullable();
            $table->string('sck')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celcash_payments', function (Blueprint $table) {
            $table->dropColumn([
                'src',
                'sck',
                'utm_source',
                'utm_campaign',
                'utm_medium',
                'utm_content',
                'utm_term',
            ]);
        });
    }
};
