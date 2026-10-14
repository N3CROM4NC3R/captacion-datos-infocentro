<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columnas de las secciones E, F y G que viven directamente en el
     * infocentro. El resto de cada sección va en actividades, equipos y
     * energias.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            // E. Modelo de gestión y articulación comunitaria
            $table->string('vinculacion_sac')->nullable()->after('observaciones_facilitadores');
            $table->string('vinculacion_sac_desde')->nullable()->after('vinculacion_sac');
            $table->text('vinculacion_sac_proyectos')->nullable()->after('vinculacion_sac_desde');
            $table->string('vinculacion_consejos')->nullable()->after('vinculacion_sac_proyectos');
            $table->unsignedSmallInteger('consejos_cantidad')->nullable()->after('vinculacion_consejos');
            $table->text('vinculacion_consejos_proyectos')->nullable()->after('consejos_cantidad');
            $table->boolean('vinculacion_autoridad')->default(false)->after('vinculacion_consejos_proyectos');
            $table->string('autoridad_institucion')->nullable()->after('vinculacion_autoridad');
            $table->text('autoridad_proyecto')->nullable()->after('autoridad_institucion');
            $table->string('consultas_populares')->nullable()->after('autoridad_proyecto');
            $table->text('consultas_populares_detalle')->nullable()->after('consultas_populares');
            $table->string('mesa_tecnica')->nullable()->after('consultas_populares_detalle');
            $table->string('participacion_comunidad')->nullable()->after('mesa_tecnica');
            $table->text('observaciones_gestion')->nullable()->after('participacion_comunidad');

            // F. Oferta formativa anual y observaciones
            $table->string('oferta_formativa_anual')->nullable()->after('observaciones_gestion');
            $table->string('oferta_formativa_define')->nullable()->after('oferta_formativa_anual');
            $table->text('observaciones_actividades')->nullable()->after('oferta_formativa_define');

            // G. Suficiencia del equipamiento
            $table->string('suficiencia_equipamiento')->nullable()->after('observaciones_actividades');
            $table->text('equipamiento_falta')->nullable()->after('suficiencia_equipamiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->dropColumn([
                'vinculacion_sac',
                'vinculacion_sac_desde',
                'vinculacion_sac_proyectos',
                'vinculacion_consejos',
                'consejos_cantidad',
                'vinculacion_consejos_proyectos',
                'vinculacion_autoridad',
                'autoridad_institucion',
                'autoridad_proyecto',
                'consultas_populares',
                'consultas_populares_detalle',
                'mesa_tecnica',
                'participacion_comunidad',
                'observaciones_gestion',
                'oferta_formativa_anual',
                'oferta_formativa_define',
                'observaciones_actividades',
                'suficiencia_equipamiento',
                'equipamiento_falta',
            ]);
        });
    }
};
