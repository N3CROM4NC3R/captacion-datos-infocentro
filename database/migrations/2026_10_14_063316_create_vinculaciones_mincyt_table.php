<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una fila por institución del MINCYT con la que el infocentro se vincula
     * (sección H.2). Es una lista libre, a diferencia de actividades, equipos y
     * energías, que son catálogos cerrados por tipo.
     */
    public function up(): void
    {
        Schema::create('vinculaciones_mincyt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infocentro_id')->constrained()->cascadeOnDelete();
            $table->string('institucion');
            $table->string('proyecto')->nullable();
            $table->string('estado')->nullable();
            $table->date('ultimo_contacto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vinculaciones_mincyt');
    }
};
