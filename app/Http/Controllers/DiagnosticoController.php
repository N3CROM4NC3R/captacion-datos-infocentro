<?php

namespace App\Http\Controllers;

use App\Enums\CargoFirmaEnum;
use App\Enums\DiaSemanaEnum;
use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\DocumentoAnexoEnum;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiagnosticoController extends Controller
{
    /**
     * Display the diagnosis form for the signed in user's infocentro.
     */
    public function edit(Request $request): View
    {
        $infocentro = $request->user()->infocentro;

        $infocentro->load(['facilitadores', 'actividades', 'equipos', 'energias', 'vinculacionesMincyt', 'documentosAnexos']);

        $estadoId = $request->old('estado_id', $infocentro->estado_id);
        $municipioId = $request->old('municipio_id', $infocentro->municipio_id);
        $parroquiaId = $request->old('parroquia_id', $infocentro->parroquia_id);

        $puntuacionesAEditar = $this->puntuacionesAEditar($request);

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
            'documentos' => $infocentro->documentosAnexos->keyBy(fn ($documento) => $documento->tipo->value),
            'puntuaciones' => $puntuacionesAEditar,
            // Indexado por el valor del enum (a, b, c) y en el orden de sus
            // casos: el desplegable de la vista pinta el texto, pero es
            // Alpine quien calcula el tipo, y busca el primer umbral que
            // alcance el total.
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

        // El número de bloques lo decide el contador de H.2, así que solo se
        // devuelven las instituciones ya registradas: rellenar con filas vacías
        // las haría aparecer en el formulario sin estar guardadas.
        return $guardadas;
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
                'formato' => $request->old("equipos.formato.{$tipo->value}", $fila?->formato?->value),
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

        $puntuaciones = $this->puntuacionesValidadas($request);
        $total = $puntuaciones === [] ? null : array_sum($puntuaciones);

        $datos = $request->safe()->except([
            'facilitadores', 'actividades', 'equipos', 'energias', 'vinculaciones',
            'documentos', 'firmas', 'puntuaciones',
        ]);

        DB::transaction(function () use ($infocentro, $datos, $request, $puntuaciones, $total): void {
            $infocentro->update([
                ...$datos,
                'tiene_facilitador_titular' => $request->boolean('tiene_facilitador_titular'),
                'vinculacion_autoridad' => $request->boolean('vinculacion_autoridad'),
                'vinculacion_mincyt' => $request->boolean('vinculacion_mincyt'),
                'acomp_fundacite' => $request->boolean('acomp_fundacite'),
                'ciencia_ciudadana' => $request->boolean('ciencia_ciudadana'),
                // El select de estatus llega como "1" o "0", así que el booleano
                // hay que resolverlo a mano. La razón solo aplica mientras el
                // infocentro esté inactivo: reactivarlo la deja obsoleta.
                'activo' => $request->boolean('activo'),
                'razon_cierre' => $request->boolean('activo')
                    ? null
                    : $this->textoOpcional($request->input('razon_cierre')),
                // El bloque de firmas es fijo y se captura entero en cada
                // guardado, a diferencia de los documentos anexos.
                'firmas' => $this->firmasValidadas($request),
                // El total y el tipo no vienen del formulario: se deducen de
                // las siete puntuaciones, que es la única forma de que la
                // clasificación no se contradiga a sí misma.
                'puntuaciones' => $puntuaciones,
                'puntuacion_total' => $total,
                'tipo_infocentro' => $total === null
                    ? null
                    : TipoInfocentroEnum::desdePuntuacion($total),
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

            // Los documentos anexos sí se capturan enteras en cada guardado,
            // pero no se reemplazan: quien edita puede haber tocado cualquier
            // campo sin volver a elegir los archivos.
            $this->guardarDocumentos($infocentro, $request);
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
            $formato = $tipo->tieneFormato() ? ($payload['formato'][$tipo->value] ?? null) : null;
            $detalle = $this->textoOpcional($payload['detalle'][$tipo->value] ?? null);

            // Sin cantidad, estado, formato ni detalle el bloque no aporta nada,
            // así que no se guarda. `formato` y `detalle` cuentan porque elegir
            // «portátiles» o describir un equipo suelto sigue siendo información
            // válida aunque no haya cantidad.
            if ($cantidad === null && $estado === null && $formato === null && $detalle === null) {
                continue;
            }

            $filas[] = [
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'funcionando' => $tipo->tieneDesglose() ? ($payload['funcionando'][$tipo->value] ?? null) : null,
                'fuera_servicio' => $tipo->tieneDesglose() ? ($payload['fuera_servicio'][$tipo->value] ?? null) : null,
                'estado' => $estado,
                'detalle' => $detalle,
                'formato' => $formato,
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
            $observaciones = $payload['observaciones'][$tipo->value] ?? null;
            $detalle = $this->textoOpcional($payload['detalle'][$tipo->value] ?? null);

            if ($cantidad === null && $estado === null && $detalle === null && blank($observaciones)) {
                continue;
            }

            $filas[] = [
                'tipo' => $tipo,
                'cantidad' => $tipo->cantidadFija() ? $cantidad : null,
                'estado' => $estado,
                'detalle' => $detalle,
                'observaciones' => $this->textoOpcional($observaciones),
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
     * Build the dimension scores shown by the form, preferring the rejected
     * submission so a validation error does not wipe what was chosen.
     *
     * @return array<string, int>
     */
    private function puntuacionesAEditar(Request $request): array
    {
        $guardadas = $request->user()->infocentro->puntuaciones ?? [];

        if (is_array($request->old('puntuaciones'))) {
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
     * Build the signature block, dropping the people left blank.
     *
     * @return array<string, array<string, string>>
     */
    private function firmasValidadas(GuardarDiagnosticoRequest $request): array
    {
        $firmas = [];

        foreach (CargoFirmaEnum::cases() as $cargo) {
            $datos = (array) $request->input("firmas.{$cargo->value}", []);
            $campos = array_filter([
                'nombre' => $datos['nombre'] ?? null,
                'firma' => $datos['firma'] ?? null,
                'fecha' => $datos['fecha'] ?? null,
            ], fn (mixed $valor): bool => filled($valor));

            if ($campos !== []) {
                $firmas[$cargo->value] = $campos;
            }
        }

        return $firmas;
    }

    /**
     * Normalise the dimension scores to integers keyed by dimension.
     *
     * Solo se guardan las dimensiones del enum: una clave desconocida se
     * descarta aquí aunque hubiera pasado la validación.
     *
     * @return array<string, int>
     */
    private function puntuacionesValidadas(GuardarDiagnosticoRequest $request): array
    {
        $puntuaciones = [];

        foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
            $valor = $request->input("puntuaciones.{$dimension->value}");

            if ($valor !== null && $valor !== '') {
                $puntuaciones[$dimension->value] = (int) $valor;
            }
        }

        return $puntuaciones;
    }

    /**
     * Store the documents attached in section J.
     *
     * A diferencia del resto de secciones, los archivos NO se borran en cada
     * guardado: un `<input type="file">` llega vacío cuando el facilitador no
     * toca ese campo, así que vaciar la colección tiraría a la basura lo ya
     * subido. Solo se reemplaza el tipo que llega con archivo nuevo, y el
     * borrado es una acción explícita.
     *
     * El archivo se escribe dentro de la transacción, así que una reversión
     * deja el PDF huérfano en el disco. Es inocuo: la ruta es determinista y el
     * siguiente guardado lo sobrescribe.
     */
    private function guardarDocumentos(Infocentro $infocentro, GuardarDiagnosticoRequest $request): void
    {
        $disco = Storage::disk(config('filesystems.default'));

        foreach (DocumentoAnexoEnum::cases() as $tipo) {
            $valor = $tipo->value;
            $existente = $infocentro->documentosAnexos()->where('tipo', $valor)->first();

            if ($request->boolean("documentos_eliminar.$valor")) {
                $disco->delete($existente?->ruta ?? '');

                $existente?->delete();

                continue;
            }

            $archivo = $request->file("documentos.$valor");

            if ($archivo === null) {
                continue;
            }

            // Ruta determinista y sin nada del usuario dentro: escribir sobre
            // el mismo destino sustituye el archivo anterior.
            $ruta = "diagnostico/{$infocentro->id}/{$valor}.pdf";

            abort_unless(
                $archivo->storeAs("diagnostico/{$infocentro->id}", "{$valor}.pdf") !== false,
                500,
                __('No se pudo guardar el archivo adjunto.'),
            );

            $datos = [
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'mime' => $archivo->getClientMimeType(),
                'tamano' => $archivo->getSize(),
                'etiqueta' => $tipo->etiquetaDetalle() === null
                    ? null
                    : $this->textoOpcional($request->input("documentos_etiqueta.$valor")),
            ];

            $existente === null
                ? $infocentro->documentosAnexos()->create(['tipo' => $valor, ...$datos])
                : $existente->update($datos);
        }
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
