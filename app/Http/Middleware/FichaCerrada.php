<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la ficha de diagnóstico en cuanto se guarda.
 *
 * Va aquí y no en el controller porque el `FormRequest` valida antes de que la
 * acción se ejecute: un corte dentro del controller llegaría después de
 * validar y quien reintentara el guardado recibiría errores de un formulario que
 * en realidad ya no puede usarse.
 *
 * Se responde con una redirección y no con un 403 porque el caso normal es ese:
 * alguien que tenía el formulario abierto en otra pestaña y lo mandó otra vez.
 * Un «no autorizado» a secas no le dice qué ha pasado; volver a su ficha, ya en
 * solo lectura y con el aviso, sí.
 */
class FichaCerrada
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $infocentro = $request->user()?->infocentro;

        if ($infocentro?->diagnostico_guardado_at !== null) {
            return redirect()
                ->route('infocentro.diagnostico.edit')
                ->with('aviso', __('Tu ficha ya está guardada y no se puede volver a modificar. Si necesitas corregir algo, pide a un administrador que la vacíe para poder volver a llenarla.'));
        }

        return $next($request);
    }
}
