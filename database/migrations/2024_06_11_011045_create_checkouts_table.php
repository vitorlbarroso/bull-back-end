<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offering_id')->nullable()->constrained('products_offerings')->nullOnDelete();;
            $table->string('checkout_hash');
            $table->boolean('is_active')->default(true);
            $table->string('order_bump_title')->default('Adicione esses outros produtos com um mega desconto!')->nullable();
            $table->string('checkout_title');
            $table->string('background_color')->default('#FFFFFF');
            $table->boolean('whatsapp_is_active')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->boolean('exit_popup')->default(false);
            $table->string('whatsapp_message')->nullable();
            $table->foreignId('banner_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('timer_id')->nullable()->constrained('timers')->cascadeOnDelete();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
