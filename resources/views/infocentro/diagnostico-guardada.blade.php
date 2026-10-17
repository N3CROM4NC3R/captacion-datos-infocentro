<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ficha de diagnóstico territorial del infocentro') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('aviso'))
                <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                    {{ session('aviso') }}
                </div>
            @endif

            <div class="mb-6 rounded-md bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                <p class="font-medium">
                    {{ __('Tu ficha está guardada.') }}
                    @if ($infocentro->diagnostico_guardado_at)
                        <span class="font-normal">
                            {{ __('Se envió el') }}
                            {{ $infocentro->diagnostico_guardado_at->format('d/m/Y') }}
                            {{ __('a las') }}
                            {{ $infocentro->diagnostico_guardado_at->format('H:i') }}.
                        </span>
                    @endif
                </p>
                <p class="mt-1">
                    {{ __('Ya no se puede modificar. Si necesitas corregir algo, pide a un administrador que la vacíe para poder volver a llenarla.') }}
                </p>
            </div>

            {{-- Los mismos partials que el formulario, dentro de un `fieldset`
                 disabled: es el patrón que ya usa el visor del panel, así que
                 la ficha se ve igual allí y aquí sin maquetarla dos veces. Sin
                 este fieldset los campos se verían normales y parecerían
                 editables. --}}
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

            {{-- Sin botón de guardar: la ficha está cerrada. Lo que queda son las
                 dos salidas de siempre, para que quien llega hasta aquí no se
                 quede en un callejón. --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('profile.edit') }}" class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    {{ __('Mi perfil') }}
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                        {{ __('Cerrar sesión') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            {{-- Sin este payload, lo que usa `x-cloak` —los campos condicionales
                 que sí están rellenados— se queda oculto para siempre. --}}
            {{-- El mismo payload que el visor del panel, y por el mismo motivo:
                 sin él, lo que usa `x-cloak` —los campos condicionales que sí
                 están rellenados— se queda oculto para siempre. --}}
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