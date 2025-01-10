<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members_area_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('members_area_id')->references('id')->on('members_area')->onDelete('cascade');;
            $table->foreignId('product_offering_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members_area_offers');
    }
};
