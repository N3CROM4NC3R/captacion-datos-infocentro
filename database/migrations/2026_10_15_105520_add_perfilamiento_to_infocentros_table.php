<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Perfilamiento y clasificación (J.1-J.4). Las siete puntuaciones van en
     * JSON porque su número es fijo; el total y el tipo van en columnas propias
     * porque son lo que el panel consulta para clasificar a los 787
     * infocentros sin recorrer 787 JSON.
     *
     * El total y el tipo no se reciben del formulario: el controller los
     * calcula a partir de las puntuaciones cada vez que se guarda.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->json('puntuaciones')->nullable()->after('firmas');
            $table->unsignedTinyInteger('puntuacion_total')->nullable()->after('puntuaciones');
            $table->string('tipo_infocentro')->nullable()->after('puntuacion_total');
            $table->string('prioridad_intervencion')->nullable()->after('tipo_infocentro');
            $table->text('recomendaciones')->nullable()->after('prioridad_intervencion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Se quitan de una en una: en PostgreSQL una sola sentencia con varias
     * columnas no admite `IF EXISTS`, y basta con que falle una para que la
     * reversión a medias deje la tabla en un estado que ya no corresponde al
     * código.
     */
    public function down(): void
    {
        foreach ([
            'recomendaciones',
            'prioridad_intervencion',
            'tipo_infocentro',
            'puntuacion_total',
            'puntuaciones',
        ] as $columna) {
            Schema::table('infocentros', function (Blueprint $table) use ($columna) {
                if (Schema::hasColumn('infocentros', $columna)) {
                    $table->dropColumn($columna);
                }
            });
        }
    }
};
