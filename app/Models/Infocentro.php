<?php

namespace App\Models;

use App\Enums\CondicionEspacioEnum;
use App\Enums\ConsultasPopularesEnum;
use App\Enums\DiaSemanaEnum;
use App\Enums\FormacionContinuaEnum;
use App\Enums\InteresDemandaEnum;
use App\Enums\MesaTecnicaEnum;
use App\Enums\OfertaAnualEnum;
use App\Enums\OperatividadEnum;
use App\Enums\ParticipacionComunidadEnum;
use App\Enums\ProveedorInternetEnum;
use App\Enums\ServicioInternetEnum;
use App\Enums\SuficienciaEquipamientoEnum;
use App\Enums\SuficienciaHorarioEnum;
use App\Enums\VinculacionEnum;
use Database\Factories\InfocentroFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'nombre',
    'codigo',
    'direccion',
    'telefono',
    'estado_id',
    'municipio_id',
    'parroquia_id',
    'comuna_circuito',
    'consejo_comunal',
    'sala_autogobierno_comunal',
    'latitud',
    'longitud',
    'fecha_diagnostico',
    'nombre_diagnosticador',
    'cargo_institucion',
    'servicio_internet',
    'proveedor_internet',
    'proveedor_nombre',
    'proveedor_detalle',
    'observaciones_conectividad',
    'operatividad',
    'operatividad_detalle',
    'dias_atencion',
    'horario_inicio',
    'horario_fin',
    'horario_suficiente',
    'horario_suficiente_motivo',
    'condicion_espacio',
    'instalaciones',
    'observaciones_operatividad',
    'tiene_facilitador_titular',
    'formacion_continua',
    'formacion_continua_responsable',
    'observaciones_facilitadores',
    'vinculacion_sac',
    'vinculacion_sac_desde',
    'vinculacion_sac_proyectos',
    'vinculacion_consejos',
    'consejos_cantidad',
    'vinculacion_consejos_proyectos',
    'vinculacion_autoridad',
    'autoridad_institucion',
    'autoridad_proyecto',
    'consultas_populares',
    'consultas_populares_detalle',
    'mesa_tecnica',
    'participacion_comunidad',
    'observaciones_gestion',
    'oferta_formativa_anual',
    'oferta_formativa_define',
    'observaciones_actividades',
    'suficiencia_equipamiento',
    'equipamiento_falta',
    'vinculacion_mincyt',
    'acomp_fundacite',
    'acomp_fundacite_detalle',
    'problemas',
    'problemas_otros',
    'saberes_recursos',
    'interes_jovenes',
    'interes_adultos_mayores',
    'ciencia_ciudadana',
    'ciencia_ciudadana_detalle',
    'activo',
    'razon_cierre',
])]
class Infocentro extends Model
{
    /** @use HasFactory<InfocentroFactory> */
    use HasFactory;

    /**
     * Get the user account that belongs to the infocentro.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get the facilitadores of the infocentro.
     */
    public function facilitadores(): HasMany
    {
        return $this->hasMany(Facilitador::class);
    }

    /**
     * Get the activities offered by the infocentro.
     */
    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class);
    }

    /**
     * Get the equipment inventory of the infocentro.
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /**
     * Get the energy sources of the infocentro.
     */
    public function energias(): HasMany
    {
        return $this->hasMany(Energia::class);
    }

    /**
     * Get the MINCYT institutions the infocentro is linked to.
     */
    public function vinculacionesMincyt(): HasMany
    {
        return $this->hasMany(VinculacionMincyt::class);
    }

    /**
     * Get the estado where the infocentro is located.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    /**
     * Get the municipio where the infocentro is located.
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Get the parroquia where the infocentro is located.
     */
    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'tiene_facilitador_titular' => 'boolean',
            'fecha_diagnostico' => 'date',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'horario_inicio' => 'datetime:H:i',
            'horario_fin' => 'datetime:H:i',
            'servicio_internet' => ServicioInternetEnum::class,
            'proveedor_internet' => ProveedorInternetEnum::class,
            'operatividad' => OperatividadEnum::class,
            'horario_suficiente' => SuficienciaHorarioEnum::class,
            'condicion_espacio' => CondicionEspacioEnum::class,
            'formacion_continua' => FormacionContinuaEnum::class,
            'dias_atencion' => 'array',
            'instalaciones' => 'array',
            'vinculacion_sac' => VinculacionEnum::class,
            'vinculacion_consejos' => VinculacionEnum::class,
            'vinculacion_autoridad' => 'boolean',
            'consejos_cantidad' => 'integer',
            'consultas_populares' => ConsultasPopularesEnum::class,
            'mesa_tecnica' => MesaTecnicaEnum::class,
            'participacion_comunidad' => ParticipacionComunidadEnum::class,
            'oferta_formativa_anual' => OfertaAnualEnum::class,
            'suficiencia_equipamiento' => SuficienciaEquipamientoEnum::class,
            'vinculacion_mincyt' => 'boolean',
            'acomp_fundacite' => 'boolean',
            'problemas' => 'array',
            'interes_jovenes' => InteresDemandaEnum::class,
            'interes_adultos_mayores' => InteresDemandaEnum::class,
            'ciencia_ciudadana' => 'boolean',
        ];
    }

    /**
     * Get the attention days as enum cases.
     *
     * @return array<int, DiaSemanaEnum>
     */
    public function dias(): array
    {
        return collect($this->dias_atencion ?? [])
            ->map(fn (string $dia): ?DiaSemanaEnum => DiaSemanaEnum::tryFrom($dia))
            ->filter()
            ->values()
            ->all();
    }
}
