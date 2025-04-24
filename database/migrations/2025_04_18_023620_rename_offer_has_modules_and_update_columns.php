<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Renomear a tabela
        Schema::rename('offer_has_modules', 'members_area_offer_has_modules');

        Schema::table('members_area_offer_has_modules', function (Blueprint $table) {
            // Remover a coluna antiga
            $table->dropColumn('product_offering_id');
            // Adicionar a nova coluna com chave estrangeira
            $table->unsignedBigInteger('members_area_offer_id')->after('id');
            $table->foreign('members_area_offer_id')
                ->references('id')->on('members_area_offers')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('members_area_offer_has_modules', function (Blueprint $table) {
            // Remover a chave estrangeira e a coluna nova
            $table->dropForeign(['members_area_offer_id']);
            $table->dropColumn('members_area_offer_id');
            // Adicionar a coluna antiga novamente
            $table->unsignedBigInteger('product_offering_id')->after('id');
            $table->foreign('product_offering_id')
                ->references('id')->on('products_offerings')
                ->onDelete('cascade');
        });
        // Renomear a tabela de volta
        Schema::rename('members_area_offer_has_modules', 'offer_has_modules');
    }
};