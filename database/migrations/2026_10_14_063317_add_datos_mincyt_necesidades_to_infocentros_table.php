<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columnas de las secciones H e I que viven directamente en el infocentro.
     * H.2 va en la tabla vinculaciones_mincyt.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            // H. Vinculación con instituciones del MINCYT y el Estado
            $table->boolean('vinculacion_mincyt')->default(false)->after('equipamiento_falta');
            $table->boolean('acomp_fundacite')->default(false)->after('vinculacion_mincyt');
            $table->string('acomp_fundacite_detalle')->nullable()->after('acomp_fundacite');

            // I. Necesidades y potencialidades del territorio
            $table->json('problemas')->nullable()->after('acomp_fundacite_detalle');
            $table->string('problemas_otros')->nullable()->after('problemas');
            $table->text('saberes_recursos')->nullable()->after('problemas_otros');
            $table->string('interes_jovenes')->nullable()->after('saberes_recursos');
            $table->string('interes_adultos_mayores')->nullable()->after('interes_jovenes');
            $table->boolean('ciencia_ciudadana')->default(false)->after('interes_adultos_mayores');
            $table->string('ciencia_ciudadana_detalle')->nullable()->after('ciencia_ciudadana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->dropColumn([
                'vinculacion_mincyt',
                'acomp_fundacite',
                'acomp_fundacite_detalle',
                'problemas',
                'problemas_otros',
                'saberes_recursos',
                'interes_jovenes',
                'interes_adultos_mayores',
                'ciencia_ciudadana',
                'ciencia_ciudadana_detalle',
            ]);
        });
    }
};
