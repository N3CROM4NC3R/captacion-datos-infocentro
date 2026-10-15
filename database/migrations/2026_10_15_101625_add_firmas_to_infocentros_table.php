<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El bloque FIRMAS del formulario original fija siempre los mismos cuatro
     * cargos, así que se guardan como una columna JSON indexada por cargo y no
     * como una tabla aparte.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->json('firmas')->nullable()->after('ciencia_ciudadana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->dropColumn('firmas');
        });
    }
};
