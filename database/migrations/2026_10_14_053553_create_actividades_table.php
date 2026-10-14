<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una fila por actividad que desarrolla el infocentro. El campo `detalle`
     * cambia de significado según el tipo: para "otras" es el nombre de la
     * actividad, para robótica el software, y así sucesivamente.
     */
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infocentro_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');
            $table->boolean('activa')->default(false);
            $table->string('frecuencia')->nullable();
            $table->unsignedInteger('participantes')->nullable();
            $table->string('detalle')->nullable();
            $table->boolean('opcion_si_no')->nullable();
            $table->string('opcion_nivel')->nullable();
            $table->timestamps();

            $table->unique(['infocentro_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};
