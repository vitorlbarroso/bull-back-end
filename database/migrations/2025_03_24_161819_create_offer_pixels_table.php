<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('offer_pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pixels_id');
            $table->foreignId('product_offering_id');
            $table->string('access_token')->nullable();
            $table->boolean('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_pixels');
    }
};
