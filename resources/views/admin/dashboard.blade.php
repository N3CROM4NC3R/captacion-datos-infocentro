<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Verde, ámbar y rojo es el mismo lenguaje que ya llevan los
                 tipos en las tablas del listado y del módulo de fichas. --}}
            @php
                $colores = [
                    'a' => 'border-green-400 dark:border-green-500',
                    'b' => 'border-amber-400 dark:border-amber-500',
                    'c' => 'border-red-400 dark:border-red-500',
                ];
            @endphp

            <h3 class="mb-2 text-sm font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('El catálogo') }}
            </h3>

            <div class="grid gap-4 sm:grid-cols-3">
                {{-- Cada ficha lleva al listado con su filtro puesto, así que el
                     número que enseña y lo que se ve al pulsar son lo mismo.
                     El anillo de foco no es opcional: sin él el teclado pasa por
                     encima sin que se note. --}}
                <a href="{{ route('admin.infocentros.index') }}"
                    class="block rounded-lg border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentros registrados') }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                        {{ number_format($total) }}
                    </p>
                </a>

                <a href="{{ route('admin.infocentros.index', ['estatus' => 'activo']) }}"
                    class="block rounded-lg border border-green-400 bg-white p-5 transition hover:border-green-500 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-green-500 dark:bg-gray-800 dark:hover:border-green-400 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentros activos') }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums text-green-600 dark:text-green-400">
                        {{ number_format($activos) }}
                    </p>
                </a>

                <a href="{{ route('admin.infocentros.index', ['estatus' => 'inactivo']) }}"
                    class="block rounded-lg border border-amber-400 bg-white p-5 transition hover:border-amber-500 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-amber-500 dark:bg-gray-800 dark:hover:border-amber-400 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentros inactivos') }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums text-amber-600 dark:text-amber-400">
                        {{ number_format($inactivos) }}
                    </p>
                </a>
            </div>

            {{-- Sin este subtítulo, un «Tipo A: 2» al lado de «792 registrados»
                 se lee como un fallo del sistema en vez de como cinco fichas de
                 un catálogo de 787 pendientes. --}}
            <h3 class="mt-8 mb-2 text-sm font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('Clasificación de las fichas guardadas') }}
            </h3>

            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                {{ number_format($total - $sinClasificar) }}
                {{ __('de') }}
                {{ number_format($total) }}
                {{ __('infocentros tienen ficha de diagnóstico guardada y clasificación.') }}
            </p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (\App\Enums\TipoInfocentroEnum::cases() as $tipo)
                    <a href="{{ route('admin.infocentros.index', ['clasificacion' => $tipo->value]) }}"
                        @class([
                            'block rounded-lg border-l-4 bg-white p-5 transition hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:bg-gray-800 dark:focus-visible:ring-offset-gray-900',
                            $colores[$tipo->value] ?? 'border-gray-200 dark:border-gray-700',
                        ])>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $tipo->label() }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $tipo->rango() }}</p>
                        <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                            {{ number_format($porTipo[$tipo->value] ?? 0) }}
                        </p>
                    </a>
                @endforeach

                {{-- El número que hace legibles los otros tres: sin él, el
                     reparto por tipos parece completo cuando en realidad los 787
                     que no tienen ficha no están en ninguna parte. --}}
                <a href="{{ route('admin.infocentros.index', ['ficha' => 'pendiente']) }}"
                    class="block rounded-lg border-l-4 border-gray-300 bg-white p-5 transition hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:focus-visible:ring-offset-gray-900">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Sin clasificar') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Todavía sin ficha guardada') }}</p>
                    <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-500 dark:text-gray-400">
                        {{ number_format($sinClasificar) }}
                    </p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>