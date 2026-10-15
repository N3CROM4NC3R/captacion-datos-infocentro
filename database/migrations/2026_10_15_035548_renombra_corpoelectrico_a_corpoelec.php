<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La fuente de energía pasa a guardarse como `corpoelec`, que es cómo la
     * nombra el formulario original. Al ser `tipo` un enum del modelo, leer una
     * fila con el valor anterior lanzaría un error al no encontrarlo en el enum,
     * así que hay que reescribir los registros antes de cambiar el enum.
     */
    public function up(): void
    {
        DB::table('energias')
            ->where('tipo', 'corpoelectrico')
            ->update(['tipo' => 'corpoelec']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('energias')
            ->where('tipo', 'corpoelec')
            ->update(['tipo' => 'corpoelectrico']);
    }
};
