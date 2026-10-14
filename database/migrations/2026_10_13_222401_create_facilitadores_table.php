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
        Schema::create('facilitadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infocentro_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('nivel_academico');
            $table->string('titulo_carrera')->nullable();
            $table->unsignedSmallInteger('anios_experiencia')->nullable();
            $table->boolean('formacion_tic')->default(false);
            $table->string('formacion_tic_detalle')->nullable();
            $table->boolean('formacion_ciencias')->default(false);
            $table->string('formacion_ciencias_area')->nullable();
            $table->unsignedSmallInteger('horas_semanales')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilitadores');
    }
};
