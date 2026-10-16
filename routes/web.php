<?php

use App\Enums\PermisoAdminEnum;
use App\Http\Controllers\Admin\AdministradorController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FichasController;
use App\Http\Controllers\Admin\InfocentroController as AdminInfocentroController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DocumentoAnexoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// La raíz es solo la puerta de entrada: la aplicación no tiene landing
// pública, así que se manda al login.
Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return $request->user()->esAdministrador()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('infocentro.diagnostico.edit');
    })->middleware('verified')->name('dashboard');

    // Los dos roles de administración. Spatie separa la lista con `|`.
    Route::middleware('role:administrador|administrador_tecnico')->prefix('admin')->name('admin.')->group(function () {
        // El panel no lleva middleware a propósito: lo comprueba el propio
        // controlador. Detrás del middleware, un administrador sin «ver» recibiría
        // un 403 sin más, cuando lo que necesita es que le digan que su cuenta no
        // tiene permisos y a quién pedírselos. El contenido —los recuentos de
        // infocentros— sí queda detrás de «ver».
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        // Cada ruta lleva su permiso. Se escriben una a una en vez de agrupar
        // porque el reparto no es uniforme: el listado es de «ver» pero el alta
        // es de «crear», y un grupo por permiso los dejaría abiertos de más.
        Route::get('/infocentros', [AdminInfocentroController::class, 'index'])
            ->middleware('permiso:'.PermisoAdminEnum::Ver->value)
            ->name('infocentros.index');

        // Las rutas de la resource se escriben una a una en vez de usar
        // `Route::resource()->middleware([...])`: eso no reparte el middleware por
        // acción, se lo pone a todas. Con el array por acción, editar exigía
        // también «crear» y nadie que no pudiera dar de alta un infocentro podía
        // corregir uno existente.
        Route::get('/infocentros/create', [AdminInfocentroController::class, 'create'])
            ->middleware('permiso:'.PermisoAdminEnum::Crear->value)
            ->name('infocentros.create');
        Route::post('/infocentros', [AdminInfocentroController::class, 'store'])
            ->middleware('permiso:'.PermisoAdminEnum::Crear->value)
            ->name('infocentros.store');
        Route::get('/infocentros/{infocentro}/edit', [AdminInfocentroController::class, 'edit'])
            ->middleware('permiso:'.PermisoAdminEnum::Editar->value)
            ->name('infocentros.edit');
        Route::match(['put', 'patch'], '/infocentros/{infocentro}', [AdminInfocentroController::class, 'update'])
            ->middleware('permiso:'.PermisoAdminEnum::Editar->value)
            ->name('infocentros.update');

        // Fuera de la resource a propósito, como las rutas de las fichas: la
        // resource no edita nada y porque borrar un infocentro se lleva su
        // cuenta y su ficha, que es una decisión que merece verse escrita.
        Route::delete('/infocentros/{infocentro}', [AdminInfocentroController::class, 'destroy'])
            ->middleware('permiso:'.PermisoAdminEnum::Eliminar->value)
            ->name('infocentros.destroy');

        // Consulta de lo capturado. Va aparte de la resource de arriba porque
        // no edita nada y porque la ruta de la ficha no cuelga del resource.
        Route::get('/fichas', [FichasController::class, 'index'])
            ->middleware('permiso:'.PermisoAdminEnum::Ver->value)
            ->name('fichas.index');
        Route::get('/fichas/{infocentro}', [FichasController::class, 'show'])
            ->middleware('permiso:'.PermisoAdminEnum::Ver->value)
            ->name('fichas.show');

        // Vaciar una ficha. La confirmación va en página aparte porque el botón
        // está en la fila del listado y es la única barrera: se lee lo que se
        // pierde antes de decidir. Va antes de la ruta de arriba para que quede
        // claro el orden, aunque con un segmento de más no se solapan.
        // Eliminar una ficha y vaciarla se lleva el mismo trabajo de campo
        // que borrar el infocentro, así que comparten permiso.
        Route::get('/fichas/{infocentro}/eliminar', [FichasController::class, 'eliminar'])
            ->middleware('permiso:'.PermisoAdminEnum::Eliminar->value)
            ->name('fichas.eliminar');
        Route::delete('/fichas/{infocentro}', [FichasController::class, 'destroy'])
            ->middleware('permiso:'.PermisoAdminEnum::Eliminar->value)
            ->name('fichas.destroy');

        // Gestión de los administradores generales. Solo el técnico entra aquí:
        // un grupo anidado dentro del de arriba, para que el general siga viendo
        // exactamente lo mismo que antes.
        Route::middleware('role:administrador_tecnico')->prefix('administradores')->name('administradores.')->group(function () {
            Route::get('/', [AdministradorController::class, 'index'])->name('index');
            Route::get('/crear', [AdministradorController::class, 'create'])->name('create');
            Route::post('/', [AdministradorController::class, 'store'])->name('store');
            Route::get('/{administrador}/editar', [AdministradorController::class, 'edit'])->name('edit');
            Route::patch('/{administrador}', [AdministradorController::class, 'update'])->name('update');
            Route::delete('/{administrador}', [AdministradorController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware('role:infocentro')->prefix('infocentro')->name('infocentro.')->group(function () {
        Route::get('/diagnostico', [DiagnosticoController::class, 'edit'])->name('diagnostico.edit');
        Route::put('/diagnostico', [DiagnosticoController::class, 'update'])->name('diagnostico.update');

        Route::get('/catalogo/municipios', [CatalogoController::class, 'municipios'])->name('catalogo.municipios');
        Route::get('/catalogo/parroquias', [CatalogoController::class, 'parroquias'])->name('catalogo.parroquias');
    });

    // Fuera de los grupos de rol a propósito: los documentos anexos los
    // descarga tanto el infocentro dueño como el administrador. La
    // comprobación de quién pregunta la hace el propio controlador.
    Route::get('/documentos/{documento}', [DocumentoAnexoController::class, 'show'])->name('documentos.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
