<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Infocentro;
use App\Models\User;
use App\Support\LectorXlsx;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Carga el catálogo de infocentros desde el archivo maestro.
 *
 * El origen es `database/data/infocentros.xlsx`. Cada fila se identifica por su
 * código, así que el seeder es idempotente: volver a cargarlo actualiza en lugar
 * de duplicar.
 *
 * La contraseña de cada usuario es su propio código. Es una simplificación de
 * carga inicial pensada para que un facilitador pueda entrar con los datos que ya
 * conoce; si el sistema llega a exponerse, hay que rotar esas contraseñas.
 */
class InfocentrosArchivoSeeder extends Seeder
{
    /**
     * Filas procesadas por bloque.
     *
     * Hashear es lo que domina el tiempo: bcrypt a 12 rondas mide ~260 ms, así que
     * las 787 filas del maestro llevan varios minutos. Se agrupa para no abrir
     * 787 transacciones y para poder mostrar avance.
     */
    private const BLOQUE = 50;

    /**
     * Estatus del maestro que significa que el infocentro no opera.
     */
    private const CERRADO = 'Cerrado';

    /**
     * Seed the infocentros declared in the master spreadsheet.
     *
     * @throws RuntimeException
     */
    public function run(LectorXlsx $lector): void
    {
        $ruta = database_path('data/infocentros.xlsx');

        $filas = $lector->filas($ruta);

        $this->validarEncabezados($ruta, $filas);

        $rol = Role::findOrCreate(RoleEnum::Infocentro);

        $this->command?->info('Cargando '.count($filas).' infocentros desde '.basename($ruta).'…');

        $barra = $this->command?->getOutput()->createProgressBar(count($filas));
        $barra?->start();

        foreach (array_chunk($filas, self::BLOQUE) as $bloque) {
            foreach ($bloque as $fila) {
                $this->crearInfocentro($fila, $rol);
            }

            $barra?->advance(count($bloque));
        }

        $barra?->finish();
        $this->command?->newLine(2);
    }

    /**
     * Create or update the infocentro and its user.
     *
     * @param  array<string, string|null>  $fila
     */
    private function crearInfocentro(array $fila, Role $rol): void
    {
        $codigo = trim((string) $fila['cod']);
        $nombre = trim((string) $fila['nombre']);
        $cerrado = trim((string) $fila['estatus']) === self::CERRADO;

        $infocentro = Infocentro::updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre' => $nombre,
                'activo' => ! $cerrado,
                'razon_cierre' => $cerrado ? 'Cerrado en el archivo maestro.' : null,
            ],
        );

        // El correo no admits duplicados y es obligatorio, así que se deriva del
        // código, que sí es único. Sigue la convención de los demás seeders.
        $infocentro->user()->updateOrCreate(
            ['username' => $codigo],
            [
                'name' => $nombre,
                'email' => mb_strtolower($codigo).'@infocentro.test',
                'password' => $codigo,
                'email_verified_at' => now(),
            ],
        );

        $infocentro->user->assignRole($rol);
    }

    /**
     * @param  array<int, array<string, string|null>>  $filas
     *
     * @throws RuntimeException
     */
    private function validarEncabezados(string $ruta, array $filas): void
    {
        $requeridos = ['cod', 'nombre', 'estatus'];

        $presentes = array_keys($filas[0]);

        $faltantes = array_values(array_diff($requeridos, $presentes));

        if ($faltantes !== []) {
            throw new RuntimeException(
                'A '.basename($ruta).' le faltan las columnas: '.implode(', ', $faltantes)
            );
        }

        $this->verificarDuplicados($ruta, $filas);
    }

    /**
     * El código es la clave natural del seeder: un duplicado haría que la
     * segunda fila sobrescribiera a la primera en silencio.
     *
     * @param  array<int, array<string, string|null>>  $filas
     *
     * @throws RuntimeException
     */
    private function verificarDuplicados(string $ruta, array $filas): void
    {
        $vistos = [];

        foreach ($filas as $numero => $fila) {
            $codigo = trim((string) ($fila['cod'] ?? ''));

            if ($codigo === '') {
                throw new RuntimeException(
                    'La fila '.($numero + 2).' de '.basename($ruta).' no tiene código.'
                );
            }

            if (isset($vistos[$codigo])) {
                throw new RuntimeException(
                    'El código «'.$codigo.'» está repetido en '.basename($ruta)
                    .' (filas '.($vistos[$codigo] + 2).' y '.($numero + 2).').'
                );
            }

            $vistos[$codigo] = $numero;
        }
    }
}
