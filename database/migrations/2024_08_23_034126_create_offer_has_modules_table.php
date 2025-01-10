<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_has_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offering_id');
            $table->foreignId('modules_id');
            $table->boolean('is_selected');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_has_modules');
    }
};
