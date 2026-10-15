<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Formato físico de los equipos: de escritorio, portátiles o ambos. Es la
     * respuesta a la pregunta G.1 del formulario, que solo aplica a las
     * computadoras; para el resto de tipos queda en null.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('formato')->nullable()->after('detalle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('formato');
        });
    }
};
