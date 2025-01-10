<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('modules', function (Blueprint $table) {
            // Adiciona a coluna members_area_id como unsignedBigInteger
            $table->unsignedBigInteger('members_area_id')->after('id');

            // Define a chave estrangeira members_area_id referenciando o id na tabela members_areas
            $table->foreign('members_area_id')->references('id')->on('members_area')->onDelete('cascade');
            // Opcional, define o comportamento de exclusão
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules', function (Blueprint $table) {
            // Remove a chave estrangeira
            $table->dropForeign(['members_area_id']);

            // Remove a coluna members_area_id
            $table->dropColumn('members_area_id');
        });
    }
};
