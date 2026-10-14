<?php

namespace App\Http\Controllers;

use App\Enums\DiaSemanaEnum;
use App\Enums\InstalacionEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Http\Requests\Infocentro\GuardarDiagnosticoRequest;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DiagnosticoController extends Controller
{
    /**
     * Display the diagnosis form for the signed in user's infocentro.
     */
    public function edit(Request $request): View
    {
        $infocentro = $request->user()->infocentro;

        $infocentro->load(['facilitadores', 'actividades', 'equipos', 'energias', 'vinculacionesMincyt']);

        $estadoId = $request->old('estado_id', $infocentro->estado_id);
        $municipioId = $request->old('municipio_id', $infocentro->municipio_id);
        $parroquiaId = $request->old('parroquia_id', $infocentro->parroquia_id);

        return view('infocentro.diagnostico', [
            'infocentro' => $infocentro,
            'facilitadores' => $this->facilitadoresAEditar($request),
            'actividades' => $this->actividadesAEditar($request),
            'equipos' => $this->equiposAEditar($request),
            'energias' => $this->energiasAEditar($request),
            'vinculaciones' => $this->vinculacionesAEditar($request),
            'estados' => Estado::orderBy('nombre')->get(),
            'municipios' => $this->opcionesMunicipios($estadoId),
            'parroquias' => $this->opcionesParroquias($municipioId),
            'estadoId' => $estadoId,
            'municipioId' => $municipioId,
            'parroquiaId' => $parroquiaId,
            'dias' => DiaSemanaEnum::options(),
            'instalaciones' => InstalacionEnum::options(),
        ]);
    }

    /**
     * Rebuild the MINCYT institution rows shown by the form.
     *
     * @return array<int, array<string, mixed>|null>
     */
    private function vinculacionesAEditar(Request $request): array
    {
        $guardadas = $request->old('vinculaciones.institucion') !== null
            ? []
            : $request->user()->infocentro->vinculacionesMincyt
                ->map(fn ($v): array => [
                    'institucion' => $v->institucion,
                    'proyecto' => $v->proyecto,
                    'estado' => $v->estado?->value,
                    'ultimo_contacto' => $v->ultimo_contacto?->format('Y-m-d'),
                ])->all();

        // El PDF propone tres bloques; se muestran los guardados y al menos tres
        // huecos, porque no hay tope de instituciones.
        return array_pad($guardadas, 3, null);
    }

    /**
     * Rebuild the activity map shown by the form, keyed by type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function actividadesAEditar(Request $request): array
    {
        $guardadas = $request->old('actividades.activa') !== null
            ? []
            : $request->user()->infocentro->actividades->keyBy(fn ($a) => $a->tipo->value)->all();

        $mapa = [];

        foreach (TipoActividadEnum::cases() as $tipo) {
            $payload = $request->old("actividades.activa.{$tipo->value}");
            $fila = $guardadas[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'activa' => $payload !== null
                    ? filter_var($payload, FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($fila?->activa ?? false),
                'frecuencia' => $request->old("actividades.frecuencia.{$tipo->value}", $fila?->frecuencia?->value),
                'participantes' => $request->old("actividades.participantes.{$tipo->value}", $fila?->participantes),
                'detalle' => $request->old("actividades.detalle.{$tipo->value}", $fila?->detalle),
                'opcion_si_no' => $request->old("actividades.opcion_si_no.{$tipo->value}", $fila?->opcion_si_no),
                'opcion_nivel' => $request->old("actividades.opcion_nivel.{$tipo->value}", $fila?->opcion_nivel?->value),
            ];
        }

        return $mapa;
    }

    /**
     * Rebuild the equipment map shown by the form, keyed by type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function equiposAEditar(Request $request): array
    {
        $guardados = $request->old('equipos.cantidad') !== null
            ? []
            : $request->user()->infocentro->equipos->keyBy(fn ($e) => $e->tipo->value)->all();

        $mapa = [];

        foreach (TipoEquipoEnum::cases() as $tipo) {
            $fila = $guardados[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'cantidad' => $request->old("equipos.cantidad.{$tipo->value}", $fila?->cantidad),
                'funcionando' => $request->old("equipos.funcionando.{$tipo->value}", $fila?->funcionando),
                'fuera_servicio' => $request->old("equipos.fuera_servicio.{$tipo->value}", $fila?->fuera_servicio),
                'estado' => $request->old("equipos.estado.{$tipo->value}", $fila?->estado?->value),
                'detalle' => $request->old("equipos.detalle.{$tipo->value}", $fila?->detalle),
                'observaciones' => $request->old("equipos.observaciones.{$tipo->value}", $fila?->observaciones),
            ];
        }

        return $mapa;
    }

    /**
     * Rebuild the energy map shown by the form, keyed by source type.
     *
     * @return array<string, array<string, mixed>>
     */
    private function energiasAEditar(Request $request): array
    {
        $guardadas = $request->old('energias.cantidad') !== null
            ? []
            : $request->user()->infocentro->energias->keyBy(fn ($e) => $e->tipo->value)->all();

        $mapa = [];

        foreach (TipoEnergiaEnum::cases() as $tipo) {
            $fila = $guardadas[$tipo->value] ?? null;

            $mapa[$tipo->value] = [
                'cantidad' => $request->old("energias.cantidad.{$tipo->value}", $fila?->cantidad),
                'estado' => $request->old("energias.estado.{$tipo->value}", $fila?->estado?->value),
                'detalle' => $request->old("energias.detalle.{$tipo->value}", $fila?->detalle),
                'observaciones' => $request->old("energias.observaciones.{$tipo->value}", $fila?->observaciones),
            ];
        }

        return $mapa;
    }

    /**
     * Persist the diagnosis form for the signed in user's infocentro.
     */
    public function update(GuardarDiagnosticoRequest $request): RedirectResponse
    {
        $infocentro = $request->user()->infocentro;

        $datos = $request->safe()->except(['facilitadores', 'actividades', 'equipos', 'energias', 'vinculaciones']);

        DB::transaction(function () use ($infocentro, $datos, $request): void {
            $infocentro->update([
                ...$datos,
                'tiene_facilitador_titular' => $request->boolean('tiene_facilitador_titular'),
                'vinculacion_autoridad' => $request->boolean('vinculacion_autoridad'),
                'vinculacion_mincyt' => $request->boolean('vinculacion_mincyt'),
                'acomp_fundacite' => $request->boolean('acomp_fundacite'),
                'ciencia_ciudadana' => $request->boolean('ciencia_ciudadana'),
            ]);

            // La ficha captura el estado completo en cada guardado, así que cada
            // colección se reemplaza en lugar de differse. Nada referencia aún a
            // un facilitador, actividad, equipo o fuente de energía; revisar si
            // eso cambia.
            $infocentro->facilitadores()->delete();
            $infocentro->facilitadores()->createMany($request->facilitadoresValidados());

            $infocentro->actividades()->delete();
            $infocentro->actividades()->createMany($this->actividadesValidadas($request));

            $infocentro->equipos()->delete();
            $infocentro->equipos()->createMany($this->equiposValidados($request));

            $infocentro->energias()->delete();
            $infocentro->energias()->createMany($this->energiasValidadas($request));

            $infocentro->vinculacionesMincyt()->delete();
            $infocentro->vinculacionesMincyt()->createMany($this->vinculacionesValidadas($request));
        });

        return redirect()
            ->route('infocentro.diagnostico.edit')
            ->with('status', __('Diagnóstico guardado correctamente.'));
    }

    /**
     * Build the MINCYT institution rows from the parallel-array payload.
     *
     * @return array<int, array<string, mixed>>
     */
    private function vinculacionesValidadas(GuardarDiagnosticoRequest $request): array
    {
        $payload = $request->validated()['vinculaciones'] ?? [];

        $filas = [];

        foreach ($payload['institucion'] ?? [] as $indice => $institucion) {
            if (blank($institucion)) {
                continue;
            }

            $filas[] = [
                'institucion' => $institucion,
                'proyecto' => $this->textoOpcional($payload['proyecto'][$indice] ?? null),
                'estado' => $payload['estado'][$indice] ?? null,
                'ultimo_contacto' => $payload['ultimo_contacto'][$indice] ?? null,
            ];
        }

        return $filas;
    }

    /**
     * Build the activity rows from the nested payload, keyed by activity type.
     *
     * @return array<int, array<string, mixed>>
     */
    private function actividadesValidadas(GuardarDiagnosticoRequest $request): array
    {
        $payload = $request->validated()['actividades'] ?? [];

        $filas = [];

        foreach (TipoActividadEnum::cases() as $tipo) {
            if (! filter_var($payload['activa'][$tipo->value] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $filas[] = [
                'tipo' => $tipo,
                'activa' => true,
                'frecuencia' => $payload['frecuencia'][$tipo->value] ?? null,
                'participantes' => $payload['participantes'][$tipo->value] ?? null,
                'detalle' => $this->textoOpcional($payload['detalle'][$tipo->value] ?? null),
                'opcion_si_no' => $tipo->tieneOpcionBinaria()
                    ? filter_var($payload['opcion_si_no'][$tipo->value] ?? false, FILTER_VALIDATE_BOOLEAN)
                    : null,
                'opcion_nivel' => $tipo->tieneNivel() ? ($payload['opcion_nivel'][$tipo->value] ?? null) : null,
            ];
        }

        return $filas;
    }

    /**
     * Build the equipment rows from the nested payload, keyed by equipment type.
     *
     * @return array<int, array<string, mixed>>
     */
    private function equiposValidados(GuardarDiagnosticoRequest $request): array
    {
        $payload = $request->validated()['equipos'] ?? [];

        $filas = [];

        foreach (TipoEquipoEnum::cases() as $tipo) {
            $estado = $payload['estado'][$tipo->value] ?? null;
            $cantidad = $payload['cantidad'][$tipo->value] ?? null;

            // Sin cantidad ni estado el bloque no aporta nada, así que no se guarda.
            if ($cantidad === null && $estado === null) {
                continue;
            }

            $filas[] = [
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'funcionando' => $tipo->tieneDesglose() ? ($payload['funcionando'][$tipo->value] ?? null) : null,
                'fuera_servicio' => $tipo->tieneDesglose() ? ($payload['fuera_servicio'][$tipo->value] ?? null) : null,
                'estado' => $estado,
                'detalle' => $this->textoOpcional($payload['detalle'][$tipo->value] ?? null),
                'observaciones' => $this->textoOpcional($payload['observaciones'][$tipo->value] ?? null),
            ];
        }

        return $filas;
    }

    /**
     * Build the energy rows from the nested payload, keyed by source type.
     *
     * @return array<int, array<string, mixed>>
     */
    private function energiasValidadas(GuardarDiagnosticoRequest $request): array
    {
        $payload = $request->validated()['energias'] ?? [];

        $filas = [];

        foreach (TipoEnergiaEnum::cases() as $tipo) {
            $cantidad = $payload['cantidad'][$tipo->value] ?? null;
            $estado = $payload['estado'][$tipo->value] ?? null;
            $detalle = $payload['detalle'][$tipo->value] ?? null;

            if ($cantidad === null && $estado === null && blank($detalle)) {
                continue;
            }

            $filas[] = [
                'tipo' => $tipo,
                'cantidad' => $tipo->cantidadFija() ? $cantidad : null,
                'estado' => $estado,
                'detalle' => $this->textoOpcional($detalle),
                'observaciones' => $this->textoOpcional($payload['observaciones'][$tipo->value] ?? null),
            ];
        }

        return $filas;
    }

    /**
     * Normalise an optional free-text field to null when it is blank.
     */
    private function textoOpcional(mixed $valor): ?string
    {
        return filled($valor) ? (string) $valor : null;
    }

    /**
     * Build the facilitator rows shown by the form, preferring the rejected
     * submission so a validation error does not wipe what was typed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function facilitadoresAEditar(Request $request): array
    {
        if (is_array($request->old('facilitadores.nombre'))) {
            return GuardarDiagnosticoRequest::filasDesde($request->old('facilitadores'));
        }

        return $request->user()->infocentro
            ->facilitadores()
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
     * Get the parroquias of the selected municipio.
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
