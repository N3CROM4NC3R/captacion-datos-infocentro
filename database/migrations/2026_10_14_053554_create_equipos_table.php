<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una fila por tipo de equipamiento inventariado en la sección G.
     */
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infocentro_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');
            $table->unsignedInteger('cantidad')->nullable();
            $table->unsignedInteger('funcionando')->nullable();
            $table->unsignedInteger('fuera_servicio')->nullable();
            $table->string('estado')->nullable();
            $table->string('detalle')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['infocentro_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
