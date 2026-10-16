<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\DiaSemanaEnum;
use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\InstalacionEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Enums\TipoInfocentroEnum;
use App\Http\Requests\Infocentro\GuardarDiagnosticoRequest;
use App\Models\Estado;
use App\Models\Infocentro;
use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Reúne todo lo que necesitan las vistas de la ficha de diagnóstico.
 *
 * Vive en un concern y no en el controller porque la ficha se pinta en dos
 * sitios: la del facilitador, que la rellena, y la de solo lectura del panel
 * de administración. Duplicar estas dos decenas de variables haría que un
 * cambio en un partial se viera en una vista y no en la otra.
 *
 * Con `$request`, cada bloque prefiere lo que se acababa de enviar tras una
 * validación rechazada; sin él, se toma lo que hay guardado. Ese es el único
 * punto de diferencia entre las dos formas de llamar.
 */
trait PresentaFicha
{
    /**
     * Get the view data for a diagnosis form.
     *
     * @return array<string, mixed>
     */
    protected function datosFicha(Infocentro $infocentro, ?Request $request = null): array
    {
        $infocentro->load(['facilitadores', 'actividades', 'equipos', 'energias', 'vinculacionesMincyt', 'documentosAnexos']);

        $estadoId = $this->old($request, 'estado_id', $infocentro->estado_id);
        $municipioId = $this->old($request, 'municipio_id', $infocentro->municipio_id);
        $parroquiaId = $this->old($request, 'parroquia_id', $infocentro->parroquia_id);

        return [
            'infocentro' => $infocentro,
            'facilitadores' => $this->facilitadoresAEditar($infocentro, $request),
            'actividades' => $this->actividadesAEditar($infocentro, $request),
            'equipos' => $this->equiposAEditar($infocentro, $request),
            'energias' => $this->energiasAEditar($infocentro, $request),
            'vinculaciones' => $this->vinculacionesAEditar($infocentro, $request),
            'estados' => Estado::orderBy('nombre')->get(),
            'municipios' => $this->opcionesMunicipios($estadoId),
            'parroquias' => $this->opcionesParroquias($municipioId),
            'estadoId' => $estadoId,
            'municipioId' => $municipioId,
            'parroquiaId' => $parroquiaId,
            'dias' => DiaSemanaEnum::options(),
            'instalaciones' => InstalacionEnum::options(),
            'documentos' => $infocentro->documentosAnexos->keyBy(fn ($documento) => $documento->tipo->value),
            'puntuaciones' => $this->puntuacionesAEditar($infocentro, $request),
            // Indexado por el valor del enum (a, b, c) y en el orden de sus
            // casos: el desplegable de la vista pinta el texto, pero es Alpine
            // quien calcula el tipo, y busca el primer umbral que alcance el
            // total.
            'tiposInfocentro' => collect(TipoInfocentroEnum::cases())
                ->mapWithKeys(fn (TipoInfocentroEnum $tipo): array => [
                    $tipo->value => [
                        'etiqueta' => $tipo->label(),
                        'rango' => $tipo->rango(),
                        'descripcion' => $tipo->descripcion(),
                        'intervencion' => $tipo->intervencion(),
                        'desde' => $tipo->desde(),
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * Read a value preferring the rejected submission, when there is one.
     *
     * La vista de solo lectura no tiene request, así que sin él el valor
     * guardado es la respuesta y no hay nada que preferir.
     */
    private function old(?Request $request, string $campo, mixed $guardado = null): mixed
    {
        return $request === null ? $guardado : $request->old($campo, $guardado);
    }

    /**
     * Build the facilitator rows shown by the form.
     *
     * @return array<int, array<string, mixed>>
     */
    private function facilitadoresAEditar(Infocentro $infocentro, ?Request $request): array
    {
        if ($request !== null && is_array($request->old('facilitadores.nombre'))) {
            return GuardarDiagnosticoRequest::filasDesde($request->old('facilitadores'));
        }

        return $infocentro->facilitadores()
            ->get()
            ->map(fn ($facilitador): array => [
                'nombre' => $facilitador->nombre,
                'nivel_academico' => $facilitador->nivel_academico?->value,
                'titulo_carrera' => $facilitador->titulo_carrera,
                'anios_experiencia' => $facilitador->anios_experiencia,
                'formacion_tic' => $facilitador->formacion_tic,
                'formacion_tic_detalle' => $facilitador->formacion_tic_detalle,
                'formacion_ciencias' => $facilitador->formacion_ciencias,
                'formacion_ciencias_area' => $facilitador->formacion_ciencias_area,
                'horas_semanales' => $facilitador->horas_semanales,
            ])
            ->values()
            ->all();
    }

    /**
     * Build the per-type dimension scores shown by the form.
     *
     * @return array<string, int>
     */
    private function puntuacionesAEditar(Infocentro $infocentro, ?Request $request): array
    {
        $guardadas = $infocentro->puntuaciones ?? [];

        if ($request !== null && is_array($request->old('puntuaciones'))) {
            $guardadas = $request->old('puntuaciones');
        }

        $puntuaciones = [];

        foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
            $valor = $guardadas[$dimension->value] ?? null;

            if ($valor !== null && $valor !== '') {
                $puntuaciones[$dimension->value] = (int) $valor;
            }
        }

        return $puntuaciones;
    }

    /**
     * Rebuild the activity map shown by the form, keyed by type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function actividadesAEditar(Infocentro $infocentro, ?Request $request): array
    {
        $guardadas = $this->old($request, 'actividades.activa') !== null
            ? []
            : $infocentro->actividades->keyBy(fn ($a) => $a->tipo->value)->all();

        $mapa = [];

        foreach (TipoActividadEnum::cases() as $tipo) {
            $payload = $this->old($request, "actividades.activa.{$tipo->value}");
            $fila = $guardadas[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'activa' => $payload !== null
                    ? filter_var($payload, FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($fila?->activa ?? false),
                'frecuencia' => $this->old($request, "actividades.frecuencia.{$tipo->value}", $fila?->frecuencia?->value),
                'participantes' => $this->old($request, "actividades.participantes.{$tipo->value}", $fila?->participantes),
                'detalle' => $this->old($request, "actividades.detalle.{$tipo->value}", $fila?->detalle),
                'opcion_si_no' => $this->old($request, "actividades.opcion_si_no.{$tipo->value}", $fila?->opcion_si_no),
                'opcion_nivel' => $this->old($request, "actividades.opcion_nivel.{$tipo->value}", $fila?->opcion_nivel?->value),
            ];
        }

        return $mapa;
    }

    /**
     * Rebuild the equipment map shown by the form, keyed by type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function equiposAEditar(Infocentro $infocentro, ?Request $request): array
    {
        $guardados = $this->old($request, 'equipos.cantidad') !== null
            ? []
            : $infocentro->equipos->keyBy(fn ($e) => $e->tipo->value)->all();

        $mapa = [];

        foreach (TipoEquipoEnum::cases() as $tipo) {
            $fila = $guardados[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'cantidad' => $this->old($request, "equipos.cantidad.{$tipo->value}", $fila?->cantidad),
                'funcionando' => $this->old($request, "equipos.funcionando.{$tipo->value}", $fila?->funcionando),
                'fuera_servicio' => $this->old($request, "equipos.fuera_servicio.{$tipo->value}", $fila?->fuera_servicio),
                'estado' => $this->old($request, "equipos.estado.{$tipo->value}", $fila?->estado?->value),
                'detalle' => $this->old($request, "equipos.detalle.{$tipo->value}", $fila?->detalle),
                'formato' => $this->old($request, "equipos.formato.{$tipo->value}", $fila?->formato?->value),
                'observaciones' => $this->old($request, "equipos.observaciones.{$tipo->value}", $fila?->observaciones),
            ];
        }

        return $mapa;
    }

    /**
     * Rebuild the energy map shown by the form, keyed by source type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function energiasAEditar(Infocentro $infocentro, ?Request $request): array
    {
        $guardadas = $this->old($request, 'energias.cantidad') !== null
            ? []
            : $infocentro->energias->keyBy(fn ($e) => $e->tipo->value)->all();

        $mapa = [];

        foreach (TipoEnergiaEnum::cases() as $tipo) {
            $fila = $guardadas[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'cantidad' => $this->old($request, "energias.cantidad.{$tipo->value}", $fila?->cantidad),
                'estado' => $this->old($request, "energias.estado.{$tipo->value}", $fila?->estado?->value),
                'detalle' => $this->old($request, "energias.detalle.{$tipo->value}", $fila?->detalle),
                'observaciones' => $this->old($request, "energias.observaciones.{$tipo->value}", $fila?->observaciones),
            ];
        }

        return $mapa;
    }

    /**
     * Rebuild the MINCYT institution rows shown by the form.
     *
     * El número de bloques lo decide el contador de H.2, así que solo se
     * devuelven las instituciones ya registradas: rellenar con filas vacías
     * las haría aparecer en el formulario sin estar guardadas.
     *
     * @return array<int, array<string, mixed>|null>
     */
    private function vinculacionesAEditar(Infocentro $infocentro, ?Request $request): array
    {
        if ($this->old($request, 'vinculaciones.institucion') !== null) {
            return [];
        }

        return $infocentro->vinculacionesMincyt
            ->map(fn ($v): array => [
                'institucion' => $v->institucion,
                'proyecto' => $v->proyecto,
                'estado' => $v->estado?->value,
                'ultimo_contacto' => $v->ultimo_contacto?->format('Y-m-d'),
            ])->all();
    }

    /**
     * Get the municipios of the selected estado.
     *
     * @return Collection<int, Municipio>
     */
    private function opcionesMunicipios(mixed $estadoId): mixed
    {
        if (blank($estadoId)) {
            return collect();
        }

        return Municipio::where('estado_id', $estadoId)->orderBy('nombre')->get(['id', 'nombre']);
    }

    /**
     * Get the parroquias of the selected municipality.
     *
     * @return Collection<int, Parroquia>
     */
    private function opcionesParroquias(mixed $municipioId): mixed
    {
        if (blank($municipioId)) {
            return collect();
        }

        return Parroquia::where('municipio_id', $municipioId)->orderBy('nombre')->get(['id', 'nombre']);
    }
}
