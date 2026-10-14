<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ficha de diagnóstico territorial del infocentro') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 rounded-md bg-blue-50 dark:bg-blue-900/40 px-4 py-3 text-sm text-blue-800 dark:text-blue-200">
                {{ __('Esta ficha debe ser llenada por el facilitador del infocentro con acompañamiento del vocero de la Sala de Autogobierno Comunal y, de ser posible, un representante del Consejo Comunal. Tiempo estimado de aplicación: 45-60 minutos.') }}
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 px-4 py-3 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Resumen de errores: la ficha tiene siete secciones y algunos
                 fallos (claves desconocidas, por ejemplo) no tienen un campo
                 visible donde mostrarse. --}}
            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900/50 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                    <p class="font-medium">{{ __('Revisa los siguientes campos:') }}</p>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('infocentro.diagnostico.update') }}"
                class="space-y-8"
                x-data="fichaDiagnostico()"
            >
                @csrf
                @method('PUT')

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

                <div class="flex items-center justify-end">
                    <x-primary-button>{{ __('Guardar') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.datosFicha = {
                urls: {
                    municipios: @js(route('infocentro.catalogo.municipios')),
                    parroquias: @js(route('infocentro.catalogo.parroquias')),
                },
                seleccion: {
                    estado: @js($estadoId),
                    municipio: @js($municipioId),
                    parroquia: @js($parroquiaId),
                },
                municipios: @js($municipios->pluck('nombre', 'id')),
                parroquias: @js($parroquias->pluck('nombre', 'id')),
                facilitadores: @js($facilitadores),
                niveles: @js(\App\Enums\NivelAcademicoEnum::options()),
                errores: @js(collect($errors->get('facilitadores'))->map(fn ($mensajes) => $mensajes[0] ?? null)->filter()->all()),
                proveedor: @js(old('proveedor_internet', $infocentro->proveedor_internet?->value)),
                operatividad: @js(old('operatividad', $infocentro->operatividad?->value)),
                horarioSuficiente: @js(old('horario_suficiente', $infocentro->horario_suficiente?->value)),
                vinculacionSac: @js(old('vinculacion_sac', $infocentro->vinculacion_sac?->value)),
                vinculacionConsejos: @js(old('vinculacion_consejos', $infocentro->vinculacion_consejos?->value)),
                vinculacionAutoridad: @json((bool) old('vinculacion_autoridad', $infocentro->vinculacion_autoridad)),
                consultasPopulares: @js(old('consultas_populares', $infocentro->consultas_populares?->value)),
                ofertaAnual: @js(old('oferta_formativa_anual', $infocentro->oferta_formativa_anual?->value)),
                suficienciaEquipo: @js(old('suficiencia_equipamiento', $infocentro->suficiencia_equipamiento?->value)),
                vinculacionMincyt: @json((bool) old('vinculacion_mincyt', $infocentro->vinculacion_mincyt)),
                acompFundacite: @json((bool) old('acomp_fundacite', $infocentro->acomp_fundacite)),
                cienciaCiudadana: @json((bool) old('ciencia_ciudadana', $infocentro->ciencia_ciudadana)),
                // El select de estatus manda "1" o "0" como texto, no como
                // booleano: Alpine tiene que comparar contra el string real.
                estatusActivo: @js((string) old('activo', $infocentro->activo ? '1' : '0')),
                vinculos: @js($vinculaciones),
                estadosProyecto: @js(\App\Enums\EstadoProyectoMincytEnum::options()),
                problemas: @js(old('problemas', $infocentro->problemas ?? [])),
            };
        </script>
    @endpush
</x-app-layout>
