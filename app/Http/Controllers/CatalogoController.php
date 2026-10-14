<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * List the municipios that belong to the given estado.
     */
    public function municipios(Request $request): JsonResponse
    {
        $municipios = Municipio::query()
            ->where('estado_id', $request->integer('estado_id'))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($municipios);
    }

    /**
     * List the parroquias that belong to the given municipio.
     */
    public function parroquias(Request $request): JsonResponse
    {
        $parroquias = Parroquia::query()
            ->where('municipio_id', $request->integer('municipio_id'))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($parroquias);
    }
}
