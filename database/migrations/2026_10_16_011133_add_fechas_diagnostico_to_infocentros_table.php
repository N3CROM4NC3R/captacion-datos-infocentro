<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Marca de que la ficha de diagnóstico se ha guardado.
     *
     * Son dos fechas y no una porque `updated_at` no sirve para esto: el panel
     * de administración también la mueve al editar un infocentro, así que no
     * distingue un toque de la ficha de un cambio de teléfono. `guardado_at`
     * queda clavada en el primer guardado —es la marca con la que se filtra— y
     * `actualizado_at` dice cuándo se tocó por última vez.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->timestamp('diagnostico_guardado_at')->nullable()->after('recomendaciones');
            $table->timestamp('diagnostico_actualizado_at')->nullable()->after('diagnostico_guardado_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * De una en una: en PostgreSQL una sola sentencia con varias columnas no
     * admite `IF EXISTS`, y basta que falle una para dejar la tabla a medias.
     */
    public function down(): void
    {
        foreach (['diagnostico_actualizado_at', 'diagnostico_guardado_at'] as $columna) {
            Schema::table('infocentros', function (Blueprint $table) use ($columna) {
                if (Schema::hasColumn('infocentros', $columna)) {
                    $table->dropColumn($columna);
                }
            });
        }
    }
};
