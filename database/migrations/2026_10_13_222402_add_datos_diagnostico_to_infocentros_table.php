<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Columnas de la "Ficha de diagnóstico territorial del infocentro",
     * agrupadas por las secciones A, B, C y D del documento.
     */
    public function up(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            // A. Identificación
            $table->foreignId('estado_id')->nullable()->after('telefono')->constrained()->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->after('estado_id')->constrained()->nullOnDelete();
            $table->foreignId('parroquia_id')->nullable()->after('municipio_id')->constrained()->nullOnDelete();
            $table->string('comuna_circuito')->nullable()->after('parroquia_id');
            $table->string('consejo_comunal')->nullable()->after('comuna_circuito');
            $table->string('sala_autogobierno_comunal')->nullable()->after('consejo_comunal');
            $table->decimal('latitud', 11, 7)->nullable()->after('sala_autogobierno_comunal');
            $table->decimal('longitud', 11, 7)->nullable()->after('latitud');
            $table->date('fecha_diagnostico')->nullable()->after('longitud');
            $table->string('nombre_diagnosticador')->nullable()->after('fecha_diagnostico');
            $table->string('cargo_institucion')->nullable()->after('nombre_diagnosticador');

            // B. Conectividad
            $table->string('servicio_internet')->nullable()->after('cargo_institucion');
            $table->string('proveedor_internet')->nullable()->after('servicio_internet');
            $table->string('proveedor_nombre')->nullable()->after('proveedor_internet');
            $table->string('proveedor_detalle')->nullable()->after('proveedor_nombre');
            $table->text('observaciones_conectividad')->nullable()->after('proveedor_detalle');

            // C. Operatividad
            $table->string('operatividad')->nullable()->after('observaciones_conectividad');
            $table->string('operatividad_detalle')->nullable()->after('operatividad');
            $table->json('dias_atencion')->nullable()->after('operatividad_detalle');
            $table->time('horario_inicio')->nullable()->after('dias_atencion');
            $table->time('horario_fin')->nullable()->after('horario_inicio');
            $table->string('horario_suficiente')->nullable()->after('horario_fin');
            $table->string('horario_suficiente_motivo')->nullable()->after('horario_suficiente');
            $table->string('condicion_espacio')->nullable()->after('horario_suficiente_motivo');
            $table->json('instalaciones')->nullable()->after('condicion_espacio');
            $table->text('observaciones_operatividad')->nullable()->after('instalaciones');

            // D. Facilitadores
            $table->boolean('tiene_facilitador_titular')->default(false)->after('observaciones_operatividad');
            $table->string('formacion_continua')->nullable()->after('tiene_facilitador_titular');
            $table->string('formacion_continua_responsable')->nullable()->after('formacion_continua');
            $table->text('observaciones_facilitadores')->nullable()->after('formacion_continua_responsable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('infocentros', function (Blueprint $table) {
            $table->dropForeign(['estado_id']);
            $table->dropForeign(['municipio_id']);
            $table->dropForeign(['parroquia_id']);

            $table->dropColumn([
                'estado_id',
                'municipio_id',
                'parroquia_id',
                'comuna_circuito',
                'consejo_comunal',
                'sala_autogobierno_comunal',
                'latitud',
                'longitud',
                'fecha_diagnostico',
                'nombre_diagnosticador',
                'cargo_institucion',
                'servicio_internet',
                'proveedor_internet',
                'proveedor_nombre',
                'proveedor_detalle',
                'observaciones_conectividad',
                'operatividad',
                'operatividad_detalle',
                'dias_atencion',
                'horario_inicio',
                'horario_fin',
                'horario_suficiente',
                'horario_suficiente_motivo',
                'condicion_espacio',
                'instalaciones',
                'observaciones_operatividad',
                'tiene_facilitador_titular',
                'formacion_continua',
                'formacion_continua_responsable',
                'observaciones_facilitadores',
            ]);
        });
    }
};
