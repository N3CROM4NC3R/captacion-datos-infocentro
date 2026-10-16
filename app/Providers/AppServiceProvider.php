<?php

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Support\EliminarInfocentro;
use App\Support\VaciarFicha;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El disco lo resuelven estas clases, no el contenedor: `Filesystem` es
        // una interfaz y el contenedor no sabría cuál de los discos de
        // `config/filesystems.php` quiere el que borra los documentos anexos.
        $this->app->bind(VaciarFicha::class, fn (): VaciarFicha => new VaciarFicha(VaciarFicha::disco()));
        $this->app->bind(EliminarInfocentro::class, fn (): EliminarInfocentro => new EliminarInfocentro(EliminarInfocentro::disco()));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        $this->enlazarSoloAdministradoresGenerales();
    }

    /**
     * Resolve `{administrador}` only to general administrator accounts.
     *
     * Sin esto, la ruta resuelve cualquier `User`: un administrador técnico
     * podría escribir a mano la URL de una cuenta de centro, de otro técnico o
     * de sí mismo y obtener su formulario de edición. Acotando el enlace de
     * ruta, esa URL da 404 en vez de cargar el registro y responderForbidden
     * después —que es mejor: no llega a existir para quien la escribe.
     *
     * @throws ModelNotFoundException
     */
    private function enlazarSoloAdministradoresGenerales(): void
    {
        Route::bind('administrador', function (string $valor): User {
            $admin = User::role(RoleEnum::Administrador)
                ->whereNull('infocentro_id')
                ->whereKey($valor)
                ->first();

            if ($admin === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$valor]);
            }

            return $admin;
        });
    }
}
