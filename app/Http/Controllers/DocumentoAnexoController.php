<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\DocumentoAnexo;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoAnexoController extends Controller
{
    /**
     * Download a document attached in section J.
     *
     * Los archivos viven en el disco privado, fuera del alcance del servidor
     * web, así que la descarga pasa por aquí y comprueba quién la pide. La
     * ruta está fuera de los grupos de rol porque un administrador también
     * tiene que poder verlos.
     */
    public function show(Request $request, DocumentoAnexo $documento): StreamedResponse
    {
        $this->autorizarAcceso($request->user(), $documento);

        abort_unless($documento->existeEnDisco(), 404, __('El archivo ya no está disponible.'));

        return Storage::disk(config('filesystems.default'))
            ->download($documento->ruta, $documento->nombreVisible());
    }

    /**
     * Limit the download to the owning infocentro and to administrators.
     */
    private function autorizarAcceso(?Authenticatable $usuario, DocumentoAnexo $documento): void
    {
        $esDueño = $usuario?->infocentro?->id === $documento->infocentro_id;
        $esAdministrador = $usuario !== null && $usuario->hasRole(RoleEnum::Administrador);

        abort_unless($esDueño || $esAdministrador, 403);
    }
}
