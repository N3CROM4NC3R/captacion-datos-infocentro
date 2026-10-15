<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El campo vuelve, pero solo donde el formulario lo pide: sistema operativo
     * de las computadoras, tipo de los kits de robótica, especificación de otros
     * equipos y de la fuente de energía «otra». Para el resto de tipos queda en
     * null y no se muestra en pantalla.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('detalle')->nullable()->after('estado');
        });

        Schema::table('energias', function (Blueprint $table) {
            $table->string('detalle')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });

        Schema::table('energias', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }
};
