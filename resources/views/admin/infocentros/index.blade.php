<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Infocentros') }}
            </h2>

            <a href="{{ route('admin.infocentros.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                {{ __('Nuevo infocentro') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 px-4 py-3 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-3 text-sm dark:bg-gray-700/40">
                {{-- Son dos hechos distintos y en una sola frase se leían mal:
                     cuántos coinciden con los filtros y cuántos tienen ficha. --}}
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    @if ($hayFiltros)
                        <p class="text-gray-600 dark:text-gray-300">
                            <span class="font-semibold">{{ number_format($infocentros->total()) }}</span>
                            {{ __('de') }}
                            <span class="font-semibold">{{ number_format($total) }}</span>
                            {{ __('infocentros coinciden con los filtros.') }}
                        </p>
                    @endif

                    <p class="text-gray-600 dark:text-gray-300">
                        <span class="font-semibold">{{ number_format($conFicha) }}</span>
                        {{ __('de') }}
                        <span class="font-semibold">{{ number_format($total) }}</span>
                        {{ __('infocentros tienen la ficha de diagnóstico guardada.') }}
                    </p>
                </div>
                <a href="{{ route('admin.fichas.index') }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                    {{ __('Ver las fichas capturadas') }}
                </a>
            </div>

            {{-- Seis filtros en rejilla: en móvil se apilan, en portátil entran cuatro por
                 fila y en grande los seis. Con cinco filtros activos saber
                 cuántos de los 792 salen es la diferencia entre filtrar y
                 adivinar. --}}
            <form method="GET" action="{{ route('admin.infocentros.index') }}"
                class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 xl:items-end">
                <div>
                    <x-input-label for="nombre" :value="__('Nombre')" />
                    <x-text-input id="nombre" name="nombre" type="search" class="mt-1 block w-full" :value="$nombre" :placeholder="__('Nombre del infocentro…')" />
                </div>

                <div>
                    <x-input-label for="codigo" :value="__('Código')" />
                    <x-text-input id="codigo" name="codigo" type="search" class="mt-1 block w-full" :value="$codigo" :placeholder="__('COJ08…')" />
                </div>

                <div>
                    <x-input-label for="estado" :value="__('Estado territorial')" />
                    <x-select-input id="estado" name="estado" class="mt-1 block w-full">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach ($estados as $opcion)
                            <option value="{{ $opcion->id }}" @selected((string) $estado === (string) $opcion->id)>{{ $opcion->nombre }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="estatus" :value="__('Estatus')" />
                    <x-select-input id="estatus" name="estatus" class="mt-1 block w-full">
                        <option value="">{{ __('Todos') }}</option>
                        <option value="activo" @selected($estatus === 'activo')>{{ __('Activo') }}</option>
                        <option value="inactivo" @selected($estatus === 'inactivo')>{{ __('Inactivo') }}</option>
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="clasificacion" :value="__('Clasificación')" />
                    <x-select-input id="clasificacion" name="clasificacion" class="mt-1 block w-full">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach (\App\Enums\TipoInfocentroEnum::cases() as $tipo)
                            <option value="{{ $tipo->value }}" @selected($clasificacion === $tipo->value)>{{ $tipo->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="ficha" :value="__('Estado de la ficha')" />
                    <x-select-input id="ficha" name="ficha" class="mt-1 block w-full">
                        <option value="">{{ __('Todas') }}</option>
                        <option value="hecha" @selected($ficha === 'hecha')>{{ __('Ficha guardada') }}</option>
                        <option value="pendiente" @selected($ficha === 'pendiente')>{{ __('Sin ficha') }}</option>
                    </x-select-input>
                </div>

                <div class="flex gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-6">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    @if ($hayFiltros)
                        <a href="{{ route('admin.infocentros.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:underline dark:text-gray-300">
                            {{ __('Limpiar') }}
                        </a>
                    @endif
                </div>
            </form>

            {{-- El modal va dentro del mismo `x-data` que la tabla: necesita su estado
                 para saber a quién se está eliminando. Va debajo de la tabla, no
                 dentro del `overflow-hidden` de la tarjeta, para que no lo recorte
                 el borde redondeado. --}}
            <div x-data="eliminarInfocentro(@js($detalle), @js(rtrim(route('admin.infocentros.index'), '/') . '/'))"
                x-on:abrir-infocentro.window="abrir($event.detail)">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Nombre') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Código') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Usuario') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Estado') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Ficha') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($infocentros as $infocentro)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $infocentro->nombre }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $infocentro->codigo }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $infocentro->user?->username ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @if ($infocentro->activo)
                                            <span class="text-green-600 dark:text-green-400">{{ __('Activo') }}</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400">{{ __('Inactivo') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @if ($infocentro->diagnostico_guardado_at)
                                            <span class="text-green-600 dark:text-green-400">{{ __('Guardada') }}</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">
                                                {{ $infocentro->diagnostico_guardado_at->format('d/m/Y') }}
                                                @if ($infocentro->tipo_infocentro)
                                                    · {{ $infocentro->tipo_infocentro->label() }}
                                                @endif
                                            </span>
                                            <a href="{{ route('admin.fichas.show', $infocentro) }}" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                                                {{ __('Ver la ficha') }}
                                            </a>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">{{ __('Sin ficha') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-end text-sm">
                                        <div class="flex items-center justify-end gap-4">
                                            <a href="{{ route('admin.infocentros.edit', $infocentro) }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                {{ __('Editar') }}
                                            </a>

                                            {{-- Solo cuando hay ficha: la ruta devuelve
                                                 404 sin ella, y un botón que lleva a un
                                                 404 es peor que no tener botón. `Editar`
                                                 queda sola en su celda, así que sigue siendo
                                                 la única acción de la fila que no destruye
                                                 nada. --}}
                                            @if ($infocentro->diagnostico_guardado_at)
                                                {{-- Los filtros del listado viajan en el enlace para
                                                     que la confirmación devuelva aquí con ellos
                                                     puestos, y `array_filter` deja fuera los vacíos
                                                     para no colgar `?q=&ficha=` de la URL.

                                                     `route()` toma el tercer argumento como
                                                     $absolute, no como parámetros extra: los que no
                                                     son parte de la ruta van dentro del array. --}}
                                                @php
                                                    $filtrosFila = array_filter([
                                                        'nombre' => $nombre,
                                                        'codigo' => $codigo,
                                                        'estado' => $estado,
                                                        'estatus' => $estatus,
                                                        'clasificacion' => $clasificacion,
                                                        'ficha' => $ficha,
                                                        'page' => $infocentros->currentPage(),
                                                    ]);
                                                @endphp
                                                <a href="{{ route('admin.fichas.eliminar', ['infocentro' => $infocentro, 'origen' => 'infocentros'] + $filtrosFila) }}"
                                                    class="text-sm text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                    {{ __('Eliminar la ficha') }}
                                                </a>
                                            @endif

                                            {{-- Abre el modal. No borra nada: el borrado solo
                                                 pasa por el formulario de dentro. --}}
                                            <button type="button"
                                                @click="$dispatch('abrir-infocentro', {{ $infocentro->id }})"
                                                class="text-sm text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                {{ __('Eliminar infocentro') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Ningún infocentro coincide con el filtro.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

                <x-modal name="eliminar-infocentro" :max-width="'2xl'">
                    {{-- `x-if` y no `x-show`: hasta que no se elige fila, el
                         `action` del formulario no tiene a quién apuntar y
                         renderizarlo sería publicar un DELETE sin destino. --}}
                    <template x-if="objetivo">
                        <div class="flex max-h-[85vh] flex-col">
                            <div class="px-6 pt-6">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('Eliminar infocentro') }}
                                </h2>

                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-gray-900 dark:text-gray-100"
                                        x-text="objetivo.nombre"></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400"
                                        x-text="objetivo.codigo"></span>
                                </p>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Va a desaparecer del catálogo, junto con:') }}
                                </p>

                                <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                    <li>
                                        {{ __('Su cuenta de acceso') }}
                                        <template x-if="objetivo.usuario">
                                            <span class="text-gray-900 dark:text-gray-100"
                                                x-text="`«${objetivo.usuario}»`"></span>
                                        </template>
                                        <template x-if="! objetivo.usuario">
                                            <span>{{ __('— no tenía ninguna.') }}</span>
                                        </template>
                                    </li>
                                    <li>{{ __('Su dirección, su teléfono y el resto de sus datos.') }}</li>
                                </ul>

                                {{-- La ficha es lo caro. Solo aparece cuando la hay. --}}
                                <div x-show="objetivo.ficha"
                                    class="mt-4 rounded-md bg-red-50 px-4 py-3 dark:bg-red-900/40">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                        {{ __('Además tiene una ficha de diagnóstico guardada, y se va con el infocentro:') }}
                                    </p>

                                    <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-red-700 dark:text-red-300">
                                        @php
                                            $secciones = [
                                                'facilitadores' => __('Facilitadores'),
                                                'equipos' => __('Equipos'),
                                                'energias' => __('Energías'),
                                                'actividades' => __('Actividades'),
                                                'vinculaciones' => __('Instituciones MINCYT'),
                                                'documentos' => __('Documentos anexos'),
                                            ];
                                        @endphp

                                        @foreach ($secciones as $clave => $etiqueta)
                                            <div class="flex justify-between gap-2">
                                                <dt>{{ $etiqueta }}</dt>
                                                <dd class="font-medium tabular-nums" x-text="objetivo.{{ $clave }}"></dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>

                                <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">
                                    {{ __('No se puede deshacer.') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3 bg-gray-50 px-6 py-4 dark:bg-gray-900/30">
                                <a :href="pagina"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    {{ __('Cancelar') }}
                                </a>

                                <form method="POST" :action="action" autocomplete="off">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button>
                                        {{ __('Sí, eliminar el infocentro') }}
                                    </x-danger-button>
                                </form>
                            </div>
                        </div>
                    </template>
                </x-modal>
            </div>

            <div class="mt-4">
                {{ $infocentros->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
