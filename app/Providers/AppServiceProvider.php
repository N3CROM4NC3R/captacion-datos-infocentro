<?php

namespace App\Providers;

use App\Support\EliminarInfocentro;
use App\Support\VaciarFicha;
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
    }
}
