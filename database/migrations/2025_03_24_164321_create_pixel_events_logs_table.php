<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('logs_pixel_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offering_id');
            $table->string('event_name');
            $table->string('TID')->nullable();
            $table->json('payload');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pixel_events_logs');
    }
};
