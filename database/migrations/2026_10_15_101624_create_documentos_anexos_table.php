<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Un PDF por cada tipo de documento anexo (J). El PDF original marca las
     * casillas, así que el infocentro puede tener como mucho un archivo de cada
     * tipo: lo que se repita es la etiqueta de «Otros».
     */
    public function up(): void
    {
        Schema::create('documentos_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('infocentro_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');
            $table->string('etiqueta')->nullable();
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->timestamps();

            $table->unique(['infocentro_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_anexos');
    }
};
