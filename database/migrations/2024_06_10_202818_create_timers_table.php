<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timers', function (Blueprint $table) {
            $table->id();
            $table->string('timer_title')->default('Vagas esgotando!');
            $table->string('timer_title_color')->default('#FFFFFF');
            $table->string('timer_icon_color')->default('#FFFFFF');
            $table->string('timer_bg_color')->default('#000000');
            $table->string('timer_progressbar_bg_color')->default('#000000');
            $table->string('timer_progressbar_color')->default('#FFFFFF');
            $table->string('end_timer_title')->default('Finalize a compra agora!');
            $table->string('countdown')->default('00:15:00');
            $table->boolean('display')->default(true);
            $table->boolean('is_fixed')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
