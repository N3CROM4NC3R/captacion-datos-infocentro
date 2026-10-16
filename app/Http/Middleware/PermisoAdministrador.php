<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta las rutas del panel que el administrador no tiene permitidas.
 *
 * El técnico tiene los cuatro permisos y no es editable desde la interfaz, así
 * que no hace falta un caso especial para él: la comprobación es la misma para
 * todo el mundo.
 */
class PermisoAdministrador
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->hasAnyPermission($permiso), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
