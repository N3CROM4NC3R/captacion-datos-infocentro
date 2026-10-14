<?php

namespace App\Http\Requests\Infocentro;

use App\Enums\CondicionEspacioEnum;
use App\Enums\ConsultasPopularesEnum;
use App\Enums\DiaSemanaEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\EstadoProyectoMincytEnum;
use App\Enums\FormacionContinuaEnum;
use App\Enums\FrecuenciaEnum;
use App\Enums\InstalacionEnum;
use App\Enums\InteresDemandaEnum;
use App\Enums\MesaTecnicaEnum;
use App\Enums\NivelAcademicoEnum;
use App\Enums\NivelOfimaticaEnum;
use App\Enums\OfertaAnualEnum;
use App\Enums\OperatividadEnum;
use App\Enums\ParticipacionComunidadEnum;
use App\Enums\ProblemaTerritorioEnum;
use App\Enums\ProveedorInternetEnum;
use App\Enums\ServicioInternetEnum;
use App\Enums\SuficienciaEquipamientoEnum;
use App\Enums\SuficienciaHorarioEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Enums\VinculacionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarDiagnosticoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // A. Identificación
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado_id' => ['nullable', 'integer', 'exists:estados,id'],
            'municipio_id' => [
                'nullable',
                'integer',
                Rule::exists('municipios', 'id')->where(
                    fn ($query) => $query->where('estado_id', $this->input('estado_id'))
                ),
            ],
            'parroquia_id' => [
                'nullable',
                'integer',
                Rule::exists('parroquias', 'id')->where(
                    fn ($query) => $query->where('municipio_id', $this->input('municipio_id'))
                ),
            ],
            'comuna_circuito' => ['nullable', 'string', 'max:255'],
            'consejo_comunal' => ['nullable', 'string', 'max:255'],
            'sala_autogobierno_comunal' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'fecha_diagnostico' => ['nullable', 'date'],
            'nombre_diagnosticador' => ['nullable', 'string', 'max:255'],
            'cargo_institucion' => ['nullable', 'string', 'max:255'],

            // B. Conectividad
            'servicio_internet' => ['nullable', Rule::enum(ServicioInternetEnum::class)],
            'proveedor_internet' => ['nullable', Rule::enum(ProveedorInternetEnum::class)],
            'proveedor_nombre' => [
                Rule::requiredIf(fn (): bool => $this->proveedorRequiereNombre()),
                'nullable',
                'string',
                'max:255',
            ],
            'proveedor_detalle' => ['nullable', 'string', 'max:255'],
            'observaciones_conectividad' => ['nullable', 'string', 'max:2000'],

            // C. Operatividad
            'operatividad' => ['nullable', Rule::enum(OperatividadEnum::class)],
            'operatividad_detalle' => [
                Rule::requiredIf(fn (): bool => $this->operatividadRequiereDetalle()),
                'nullable',
                'string',
                'max:255',
            ],
            'dias_atencion' => ['nullable', 'array'],
            'dias_atencion.*' => ['string', Rule::enum(DiaSemanaEnum::class)],
            'horario_inicio' => ['nullable', 'date_format:H:i'],
            'horario_fin' => ['nullable', 'date_format:H:i', 'after:horario_inicio'],
            'horario_suficiente' => ['nullable', Rule::enum(SuficienciaHorarioEnum::class)],
            'horario_suficiente_motivo' => [
                Rule::requiredIf(fn (): bool => $this->horarioRequiereMotivo()),
                'nullable',
                'string',
                'max:255',
            ],
            'condicion_espacio' => ['nullable', Rule::enum(CondicionEspacioEnum::class)],
            'instalaciones' => ['nullable', 'array'],
            'instalaciones.*' => ['string', Rule::enum(InstalacionEnum::class)],
            'observaciones_operatividad' => ['nullable', 'string', 'max:2000'],

            // D. Facilitadores
            'tiene_facilitador_titular' => ['boolean'],
            'formacion_continua' => ['nullable', Rule::enum(FormacionContinuaEnum::class)],
            'formacion_continua_responsable' => ['nullable', 'string', 'max:255'],
            'observaciones_facilitadores' => ['nullable', 'string', 'max:2000'],

            'facilitadores' => ['nullable', 'array'],
            'facilitadores.nombre' => ['array'],
            'facilitadores.nombre.*' => ['required', 'string', 'max:255'],
            'facilitadores.nivel_academico' => ['array'],
            'facilitadores.nivel_academico.*' => ['required', Rule::enum(NivelAcademicoEnum::class)],
            'facilitadores.titulo_carrera' => ['nullable', 'array'],
            'facilitadores.titulo_carrera.*' => ['nullable', 'string', 'max:255'],
            'facilitadores.anios_experiencia' => ['nullable', 'array'],
            'facilitadores.anios_experiencia.*' => ['nullable', 'integer', 'min:0', 'max:80'],
            'facilitadores.formacion_tic' => ['nullable', 'array'],
            'facilitadores.formacion_tic_detalle' => ['nullable', 'array'],
            'facilitadores.formacion_tic_detalle.*' => ['nullable', 'string', 'max:255'],
            'facilitadores.formacion_ciencias' => ['nullable', 'array'],
            'facilitadores.formacion_ciencias_area' => ['nullable', 'array'],
            'facilitadores.formacion_ciencias_area.*' => ['nullable', 'string', 'max:255'],
            'facilitadores.horas_semanales' => ['nullable', 'array'],
            'facilitadores.horas_semanales.*' => ['nullable', 'integer', 'min:0', 'max:168'],

            // E. Modelo de gestión y articulación comunitaria
            'vinculacion_sac' => ['nullable', Rule::enum(VinculacionEnum::class)],
            'vinculacion_sac_desde' => [
                Rule::requiredIf(fn (): bool => $this->vinculacion('vinculacion_sac')?->requiereDetalle() ?? false),
                'nullable',
                'string',
                'max:255',
            ],
            'vinculacion_sac_proyectos' => ['nullable', 'string', 'max:2000'],
            'vinculacion_consejos' => ['nullable', Rule::enum(VinculacionEnum::class)],
            'consejos_cantidad' => ['nullable', 'integer', 'min:0', 'max:99'],
            'vinculacion_consejos_proyectos' => ['nullable', 'string', 'max:2000'],
            'vinculacion_autoridad' => ['boolean'],
            'autoridad_institucion' => [
                Rule::requiredIf(fn (): bool => $this->boolean('vinculacion_autoridad')),
                'nullable',
                'string',
                'max:255',
            ],
            'autoridad_proyecto' => ['nullable', 'string', 'max:2000'],
            'consultas_populares' => ['nullable', Rule::enum(ConsultasPopularesEnum::class)],
            'consultas_populares_detalle' => [
                Rule::requiredIf(fn (): bool => ConsultasPopularesEnum::tryFrom((string) $this->input('consultas_populares'))?->requiereDetalle() ?? false),
                'nullable',
                'string',
                'max:2000',
            ],
            'mesa_tecnica' => ['nullable', Rule::enum(MesaTecnicaEnum::class)],
            'participacion_comunidad' => ['nullable', Rule::enum(ParticipacionComunidadEnum::class)],
            'observaciones_gestion' => ['nullable', 'string', 'max:2000'],

            // F. Oferta formativa anual
            'oferta_formativa_anual' => ['nullable', Rule::enum(OfertaAnualEnum::class)],
            'oferta_formativa_define' => [
                Rule::requiredIf(fn (): bool => OfertaAnualEnum::tryFrom((string) $this->input('oferta_formativa_anual'))?->requiereResponsable() ?? false),
                'nullable',
                'string',
                'max:255',
            ],
            'observaciones_actividades' => ['nullable', 'string', 'max:2000'],

            // F. Actividades (payload anidado por tipo)
            'actividades' => ['nullable', 'array'],
            'actividades.activa' => ['nullable', 'array'],
            'actividades.frecuencia' => ['nullable', 'array'],
            'actividades.frecuencia.*' => ['nullable', Rule::enum(FrecuenciaEnum::class)],
            'actividades.participantes' => ['nullable', 'array'],
            'actividades.participantes.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'actividades.detalle' => ['nullable', 'array'],
            'actividades.detalle.*' => ['nullable', 'string', 'max:255'],
            'actividades.opcion_si_no' => ['nullable', 'array'],
            'actividades.opcion_si_no.*' => ['nullable', 'boolean'],
            'actividades.opcion_nivel' => ['nullable', 'array'],
            'actividades.opcion_nivel.*' => ['nullable', Rule::enum(NivelOfimaticaEnum::class)],

            // G. Suficiencia del equipamiento
            'suficiencia_equipamiento' => ['nullable', Rule::enum(SuficienciaEquipamientoEnum::class)],
            'equipamiento_falta' => [
                Rule::requiredIf(fn (): bool => SuficienciaEquipamientoEnum::tryFrom((string) $this->input('suficiencia_equipamiento'))?->requiereFaltante() ?? false),
                'nullable',
                'string',
                'max:2000',
            ],

            // G. Equipos (payload anidado por tipo)
            'equipos' => ['nullable', 'array'],
            'equipos.cantidad' => ['nullable', 'array'],
            'equipos.cantidad.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'equipos.funcionando' => ['nullable', 'array'],
            'equipos.funcionando.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'equipos.fuera_servicio' => ['nullable', 'array'],
            'equipos.fuera_servicio.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'equipos.estado' => ['nullable', 'array'],
            'equipos.estado.*' => ['nullable', Rule::enum(EstadoEquipoEnum::class)],
            'equipos.detalle' => ['nullable', 'array'],
            'equipos.detalle.*' => ['nullable', 'string', 'max:255'],
            'equipos.observaciones' => ['nullable', 'array'],
            'equipos.observaciones.*' => ['nullable', 'string', 'max:2000'],

            // G. Sistemas de energía (payload anidado por tipo)
            'energias' => ['nullable', 'array'],
            'energias.cantidad' => ['nullable', 'array'],
            'energias.cantidad.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'energias.estado' => ['nullable', 'array'],
            'energias.estado.*' => ['nullable', Rule::enum(EstadoEquipoEnum::class)],
            'energias.detalle' => ['nullable', 'array'],
            'energias.detalle.*' => ['nullable', 'string', 'max:255'],
            'energias.observaciones' => ['nullable', 'array'],
            'energias.observaciones.*' => ['nullable', 'string', 'max:2000'],

            // H. Vinculación con instituciones del MINCYT y el Estado
            'vinculacion_mincyt' => ['boolean'],
            'acomp_fundacite' => ['boolean'],
            'acomp_fundacite_detalle' => [
                Rule::requiredIf(fn (): bool => $this->boolean('acomp_fundacite')),
                'nullable',
                'string',
                'max:255',
            ],

            // H.2 Instituciones (lista libre, payload en arrays paralelos)
            'vinculaciones' => ['nullable', 'array'],
            'vinculaciones.institucion' => ['array'],
            'vinculaciones.institucion.*' => ['required', 'string', 'max:255'],
            'vinculaciones.proyecto' => ['nullable', 'array'],
            'vinculaciones.proyecto.*' => ['nullable', 'string', 'max:255'],
            'vinculaciones.estado' => ['nullable', 'array'],
            'vinculaciones.estado.*' => ['nullable', Rule::enum(EstadoProyectoMincytEnum::class)],
            'vinculaciones.ultimo_contacto' => ['nullable', 'array'],
            'vinculaciones.ultimo_contacto.*' => ['nullable', 'date'],

            // I. Necesidades y potencialidades del territorio
            'problemas' => ['nullable', 'array'],
            'problemas.*' => ['string', Rule::enum(ProblemaTerritorioEnum::class)],
            'problemas_otros' => [
                Rule::requiredIf(fn (): bool => $this->marcaProblema('otros')),
                'nullable',
                'string',
                'max:255',
            ],
            'saberes_recursos' => ['nullable', 'string', 'max:2000'],
            'interes_jovenes' => ['nullable', Rule::enum(InteresDemandaEnum::class)],
            'interes_adultos_mayores' => ['nullable', Rule::enum(InteresDemandaEnum::class)],
            'ciencia_ciudadana' => ['boolean'],
            'ciencia_ciudadana_detalle' => [
                Rule::requiredIf(fn (): bool => $this->boolean('ciencia_ciudadana')),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Determine whether a given problem was selected in the I.1 multiselect.
     */
    private function marcaProblema(string $valor): bool
    {
        return in_array($valor, (array) $this->input('problemas', []), true);
    }

    /**
     * Resolve the VinculacionEnum for one of the E.1 / E.2 questions.
     */
    private function vinculacion(string $campo): ?VinculacionEnum
    {
        $valor = $this->input($campo);

        return is_string($valor) ? VinculacionEnum::tryFrom($valor) : null;
    }

    /**
     * Get custom messages for validator errors.
     *
     * Rule::requiredIf() no encadena la condición en el mensaje, así que estos
     * textos dicen explícitamente qué hay que rellenar.
     *
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'municipio_id.exists' => 'El municipio seleccionado no pertenece al estado elegido.',
            'parroquia_id.exists' => 'La parroquia seleccionada no pertenece al municipio elegido.',
            'horario_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'proveedor_nombre.required' => 'Indica el nombre de la empresa o de la red comunitaria.',
            'operatividad_detalle.required' => 'Especifica la limitación o la causa por la que no está operativo.',
            'horario_suficiente_motivo.required' => 'Explica por qué el horario no responde a las necesidades de la comunidad.',
            'vinculacion_sac_desde.required' => 'Indica desde cuándo está vinculado a la Sala de Autogobierno Comunal.',
            'autoridad_institucion.required' => 'Indica con qué institución regional está vinculado.',
            'consultas_populares_detalle.required' => 'Indica en cuáles Consultas Populares ha participado.',
            'oferta_formativa_define.required' => 'Indica quién define la oferta formativa anual.',
            'equipamiento_falta.required' => 'Indica qué falta prioritariamente.',
            'acomp_fundacite_detalle.required' => 'Indica en qué Consistió el acompañamiento de Fundacite.',
            'ciencia_ciudadana_detalle.required' => 'Indica cuáles son esas experiencias previas.',
            'problemas_otros.required' => 'Especifica cuáles son los otros problemas identificados.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Cross-checks that declarative rules cannot express: the Sí/No answers of
     * each facilitator, the activity roster, and the equipment counts.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validarClaves($validator, 'actividades', 'actividades.activa', TipoActividadEnum::class);
            $this->validarClaves($validator, 'equipos', 'equipos.estado', TipoEquipoEnum::class);
            $this->validarClaves($validator, 'energias', 'energias.estado', TipoEnergiaEnum::class);

            $this->validarFacilitadores($validator);
            $this->validarActividades($validator);
            $this->validarEquipos($validator);
            $this->validarVinculacionesMincyt($validator);
        });
    }

    /**
     * Claiming a link to the MINCYT requires listing at least one institution.
     */
    private function validarVinculacionesMincyt(Validator $validator): void
    {
        if (! $this->boolean('vinculacion_mincyt')) {
            return;
        }

        if (blank(array_filter((array) $this->input('vinculaciones.institucion', [])))) {
            $validator->errors()->add(
                'vinculaciones.institucion',
                __('Si hay vinculación con el MINCYT, indica al menos una institución.')
            );
        }
    }

    /**
     * Cross-check each facilitator's Sí/No answers against their follow-up fields.
     */
    private function validarFacilitadores(Validator $validator): void
    {
        foreach (array_keys($this->input('facilitadores.nombre', [])) as $indice) {
            if (blank($this->input("facilitadores.nombre.$indice"))) {
                continue;
            }

            if ($this->flagFacilitador('formacion_tic', $indice)
                && blank($this->input("facilitadores.formacion_tic_detalle.$indice"))) {
                $validator->errors()->add(
                    "facilitadores.formacion_tic_detalle.$indice",
                    __('Indica qué formación en TIC tiene.')
                );
            }

            if ($this->flagFacilitador('formacion_ciencias', $indice)
                && blank($this->input("facilitadores.formacion_ciencias_area.$indice"))) {
                $validator->errors()->add(
                    "facilitadores.formacion_ciencias_area.$indice",
                    __('Indica en qué área tiene formación en ciencias.')
                );
            }
        }
    }

    /**
     * An active activity needs a frequency and a participant count, and
     * "otras actividades" needs its name in the detail field.
     */
    private function validarActividades(Validator $validator): void
    {
        foreach ($this->input('actividades.activa', []) as $tipo => $activa) {
            if (! filter_var($activa, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            if (blank($this->input("actividades.frecuencia.$tipo"))) {
                $validator->errors()->add("actividades.frecuencia.$tipo", __('Indica la frecuencia con la que se desarrolla.'));
            }

            if ($this->input("actividades.participantes.$tipo") === null) {
                $validator->errors()->add("actividades.participantes.$tipo", __('Indica cuántos participantes aproximados tiene.'));
            }
        }

        if (filter_var($this->input('actividades.activa.'.TipoActividadEnum::Otras->value, false), FILTER_VALIDATE_BOOLEAN)
            && blank($this->input('actividades.detalle.'.TipoActividadEnum::Otras->value))) {
            $validator->errors()->add(
                'actividades.detalle.'.TipoActividadEnum::Otras->value,
                __('Especifica el nombre de la actividad.')
            );
        }
    }

    /**
     * Equipment marked as "no tiene" must report a quantity of zero, and the
     * working plus out-of-service units cannot exceed the total.
     *
     * Itera sobre la unión de claves presentes en el payload: si solo se envía
     * la cantidad de computadoras, el desglose sigue teniendo que validarse.
     */
    private function validarEquipos(Validator $validator): void
    {
        foreach ($this->clavesEnviadas('equipos') as $tipo) {
            if (! TipoEquipoEnum::tryFrom($tipo) instanceof TipoEquipoEnum) {
                $validator->errors()->add("equipos.estado.$tipo", __('Tipo de equipamiento desconocido.'));

                continue;
            }

            $tipoEnum = TipoEquipoEnum::from($tipo);
            $cantidad = $this->input("equipos.cantidad.$tipo");
            $estado = $this->input("equipos.estado.$tipo");

            if (EstadoEquipoEnum::tryFrom((string) $estado)?->implicaCantidadCero() && (int) $cantidad !== 0) {
                $validator->errors()->add("equipos.cantidad.$tipo", __('Si el equipo figura como "no tiene", la cantidad debe ser 0.'));
            }

            if (! $tipoEnum->tieneDesglose()) {
                continue;
            }

            $funcionando = (int) $this->input("equipos.funcionando.$tipo", 0);
            $fuera = (int) $this->input("equipos.fuera_servicio.$tipo", 0);

            if ($cantidad !== null && ($funcionando + $fuera) > (int) $cantidad) {
                $validator->errors()->add("equipos.fuera_servicio.$tipo", __('En funcionamiento y fuera de servicio no pueden sumar más que la cantidad total.'));
            }
        }
    }

    /**
     * Get every key used across the sub-payloads of a nested section.
     *
     * Las reglas declarativas solo miran el valor de cada clave, así que un tipo
     * inventado ("inventado" => "optimo") pasaría limpio. Reunir las claves de
     * todos los sub-payloads permite comprobarlas contra el enum.
     *
     * @return array<int, string>
     */
    private function clavesEnviadas(string $seccion): array
    {
        $claves = [];

        foreach ($this->input($seccion, []) as $sub => $valores) {
            if (is_array($valores)) {
                $claves = [...$claves, ...array_keys($valores)];
            }
        }

        return array_values(array_unique($claves));
    }

    /**
     * Reject keys of the nested payloads that do not exist in their enum.
     */
    private function validarClaves(Validator $validator, string $seccion, string $campo, string $enum): void
    {
        foreach ($this->clavesEnviadas($seccion) as $clave) {
            if ($enum::tryFrom($clave) === null) {
                $validator->errors()->add("$campo.$clave", __('Tipo desconocido.'));
            }
        }
    }

    /**
     * Determine whether the chosen provider needs the "¿cuál?" field.
     */
    private function proveedorRequiereNombre(): bool
    {
        return ProveedorInternetEnum::tryFrom((string) $this->input('proveedor_internet'))
            ?->requiereNombre() ?? false;
    }

    /**
     * Determine whether the chosen operational state needs the detail field.
     */
    private function operatividadRequiereDetalle(): bool
    {
        return OperatividadEnum::tryFrom((string) $this->input('operatividad'))
            ?->requiereDetalle() ?? false;
    }

    /**
     * Determine whether the chosen schedule answer needs the reason field.
     */
    private function horarioRequiereMotivo(): bool
    {
        return SuficienciaHorarioEnum::tryFrom((string) $this->input('horario_suficiente'))
            ?->requiereMotivo() ?? false;
    }

    /**
     * Determine whether a facilitator answered "sí" to one of the Sí/No checks.
     */
    private function flagFacilitador(string $campo, int|string $indice): bool
    {
        $valor = $this->input("facilitadores.$campo.$indice");

        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Turn the parallel facilitator arrays into a list of rows.
     *
     * The form posts one array per field (facilitadores[nombre][],
     * facilitators[titulo_carrera][], ...) so Alpine can repeat a block per
     * facilitator without having to renumber names after every save.
     *
     * @param  array<string, mixed>  $facilitadores
     * @return array<int, array<string, mixed>>
     */
    public static function filasDesde(array $facilitadores): array
    {
        $filas = [];

        foreach ($facilitadores['nombre'] ?? [] as $indice => $nombre) {
            if (blank($nombre)) {
                continue;
            }

            $filas[] = [
                'nombre' => $nombre,
                'nivel_academico' => $facilitadores['nivel_academico'][$indice] ?? null,
                'titulo_carrera' => $facilitadores['titulo_carrera'][$indice] ?? null,
                'anios_experiencia' => $facilitadores['anios_experiencia'][$indice] ?? null,
                'formacion_tic' => filter_var($facilitadores['formacion_tic'][$indice] ?? false, FILTER_VALIDATE_BOOLEAN),
                'formacion_tic_detalle' => $facilitadores['formacion_tic_detalle'][$indice] ?? null,
                'formacion_ciencias' => filter_var($facilitadores['formacion_ciencias'][$indice] ?? false, FILTER_VALIDATE_BOOLEAN),
                'formacion_ciencias_area' => $facilitadores['formacion_ciencias_area'][$indice] ?? null,
                'horas_semanales' => $facilitadores['horas_semanales'][$indice] ?? null,
            ];
        }

        return $filas;
    }

    /**
     * Get the validated facilitator rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function facilitadoresValidados(): array
    {
        return self::filasDesde($this->validated()['facilitadores'] ?? []);
    }
}
