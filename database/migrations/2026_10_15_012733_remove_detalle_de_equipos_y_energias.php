<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El campo «detalle» de la sección G se retiró del formulario: el inventario
     * se registra con cantidad, estado, formato (solo computadoras) y
     * observaciones. Se eliminan las columnas de ambas tablas para no dejar
     * esquema muerto.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });

        Schema::table('energias', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('detalle')->nullable()->after('estado');
        });

        Schema::table('energias', function (Blueprint $table) {
            $table->string('detalle')->nullable()->after('estado');
        });
    }
};
