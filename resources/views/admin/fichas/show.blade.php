<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $infocentro->nombre }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $infocentro->codigo }}
                    @if ($infocentro->diagnostico_guardado_at)
                        · {{ __('ficha guardada el') }} {{ $infocentro->diagnostico_guardado_at->format('d/m/Y') }}
                        · {{ __('actualizada el') }} {{ $infocentro->diagnostico_actualizado_at?->format('d/m/Y H:i') }}
                    @else
                        · {{ __('aún sin guardar') }}
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('admin.fichas.index') }}" class="text-sm text-gray-600 hover:underline dark:text-gray-300">
                    {{ __('Volver a las fichas') }}
                </a>
                <a href="{{ route('admin.infocentros.edit', $infocentro) }}" class="text-sm text-gray-600 hover:underline dark:text-gray-300">
                    {{ __('Editar el infocentro') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 rounded-md bg-blue-50 dark:bg-blue-900/40 px-4 py-3 text-sm text-blue-800 dark:text-blue-200">
                {{ __('Consulta de solo lectura. Lo que aparece aquí es lo que el facilitador y el diagnosticador capturaron; para corregirlo hay que entrar desde el infocentro.') }}
            </div>

            {{-- Los partials son los de la ficha, dentro de un fieldset
                 deshabilitado: desactiva todos los controles de una vez sin
                 tocar los partials, y así no se duplican nueve secciones de
                 maquetación. Los enlaces de descarga de la sección J siguen
                 funcionando porque son enlaces, no controles.

                 El `x-data="fichaDiagnostico()"` no sobra: varios partials no
                 declaran su propio ámbito y leen del que envuelve a toda la
                 ficha. La sección F, por ejemplo, usa `actividades[...]` de
                 ahí, así que sin él sus campos condicionales —frecuencia y
                 participantes— se quedarían ocultos aunque estuvieran
                 rellenos, y la consulta mostraría menos de lo capturado. --}}
            <fieldset disabled x-data="fichaDiagnostico()" class="space-y-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._a-identificacion')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._b-conectividad')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._c-operatividad')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._d-facilitadores')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._e-gestion')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._f-actividades')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._g-infraestructura')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._h-mincyt')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._i-necesidades')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._j-documentos')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._firmas')
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        @include('infocentro.partials._perfilamiento')
                    </div>
                </div>
            </fieldset>
        </div>
    </div>

    @push('scripts')
        <script>
            {{-- Alpine necesita el mismo estado que en la ficha del
                 facilitador: sin él, su `x-model` vaciaría los desplegables de
                 la cascada geographic y lo que usa `x-cloak` —los campos
                 condicionales que sí están rellenados— quedaría oculto. --}}
            window.datosFicha = @js([
                'urls' => [
                    'municipios' => route('infocentro.catalogo.municipios'),
                    'parroquias' => route('infocentro.catalogo.parroquias'),
                ],
                'seleccion' => [
                    'estado' => $estadoId,
                    'municipio' => $municipioId,
                    'parroquia' => $parroquiaId,
                ],
                'municipios' => $municipios->pluck('nombre', 'id'),
                'parroquias' => $parroquias->pluck('nombre', 'id'),
                'facilitadores' => $facilitadores,
                'niveles' => \App\Enums\NivelAcademicoEnum::options(),
                'errores' => [],
                'proveedor' => $infocentro->proveedor_internet?->value,
                'operatividad' => $infocentro->operatividad?->value,
                'horarioSuficiente' => $infocentro->horario_suficiente?->value,
                'vinculacionSac' => $infocentro->vinculacion_sac?->value,
                'vinculacionConsejos' => $infocentro->vinculacion_consejos?->value,
                'vinculacionAutoridad' => (bool) $infocentro->vinculacion_autoridad,
                'tieneFacilitadorTitular' => (bool) $infocentro->tiene_facilitador_titular,
                'consultasPopulares' => $infocentro->consultas_populares?->value,
                'ofertaAnual' => $infocentro->oferta_formativa_anual?->value,
                'suficienciaEquipo' => $infocentro->suficiencia_equipamiento?->value,
                'vinculacionMincyt' => (bool) $infocentro->vinculacion_mincyt,
                'acompFundacite' => (bool) $infocentro->acomp_fundacite,
                'cienciaCiudadana' => (bool) $infocentro->ciencia_ciudadana,
                'estatusActivo' => (string) ($infocentro->activo ? '1' : '0'),
                'vinculos' => $vinculaciones,
                'estadosProyecto' => \App\Enums\EstadoProyectoMincytEnum::options(),
                'problemas' => $infocentro->problemas ?? [],
                'actividades' => collect($actividades)->map(fn ($fila) => (bool) $fila['activa'])->all(),
                'puntuaciones' => array_map(fn ($valor) => (string) $valor, $puntuaciones),
                'tipos' => $tiposInfocentro,
            ]);
        </script>
    @endpush
</x-app-layout>
