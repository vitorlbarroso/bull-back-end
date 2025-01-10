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
        Schema::create('products_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('offer_name');
            $table->string('description')->nullable();
            $table->decimal('price');
            $table->decimal('fake_price');
            $table->string('sale_completed_page_url')->nullable();
            $table->string('offer_type')->default(\App\Enums\OfferTypeEnum::Unic->value);
            $table->string('charge_type')->nullable();
            $table->integer('recurrently_installments')->default(1);
            $table->boolean('enable_billet')->default(true);
            $table->boolean('enable_card')->default(true);
            $table->boolean('enable_pix')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_offerings');
    }
};
