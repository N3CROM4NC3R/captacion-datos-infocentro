<?php

use App\Enums\RoleEnum;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InfocentroController as AdminInfocentroController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// La raíz es solo la puerta de entrada: la aplicación no tiene landing
// pública, así que se manda al login.
Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return $request->user()->hasRole(RoleEnum::Administrador)
            ? redirect()->route('admin.dashboard')
            : redirect()->route('infocentro.diagnostico.edit');
    })->middleware('verified')->name('dashboard');

    Route::middleware('role:administrador')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::resource('infocentros', AdminInfocentroController::class)->except(['show', 'destroy']);
    });

    Route::middleware('role:infocentro')->prefix('infocentro')->name('infocentro.')->group(function () {
        Route::get('/diagnostico', [DiagnosticoController::class, 'edit'])->name('diagnostico.edit');
        Route::put('/diagnostico', [DiagnosticoController::class, 'update'])->name('diagnostico.update');

        Route::get('/catalogo/municipios', [CatalogoController::class, 'municipios'])->name('catalogo.municipios');
        Route::get('/catalogo/parroquias', [CatalogoController::class, 'parroquias'])->name('catalogo.parroquias');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
