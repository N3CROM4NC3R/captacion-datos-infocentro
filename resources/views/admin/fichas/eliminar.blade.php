<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Eliminar la ficha de diagnóstico') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Aviso del propio borrado, por si se llega desde otro sitio. --}}
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/50 px-4 py-3 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('Ficha a eliminar') }}
                    </p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $infocentro->nombre }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $infocentro->codigo }}
                        @if ($infocentro->estado)
                            {{-- El espacio va dentro del span porque la directiva
                                 `@if` se come el salto de línea que le sigue. --}}
                            <span class="text-gray-300 dark:text-gray-600"> · </span>{{ $infocentro->estado->nombre }}
                        @endif
                    </p>
                </div>

                <div class="px-6 py-4">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Va a desaparecer todo lo que se capturó en esta ficha:') }}
                    </p>

                    <dl class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-700">
                        @php
                            $detalle = [
                                'facilitadores' => __('Facilitadores'),
                                'equipos' => __('Equipos'),
                                'energias' => __('Energías'),
                                'actividades' => __('Actividades'),
                                'vinculaciones' => __('Instituciones vinculadas (MINCYT)'),
                                'documentos' => __('Documentos anexos'),
                            ];
                        @endphp

                        @foreach ($detalle as $clave => $etiqueta)
                            <div class="flex items-baseline justify-between gap-4 py-2">
                                <dt class="text-gray-600 dark:text-gray-400">{{ $etiqueta }}</dt>
                                <dd class="font-medium tabular-nums {{ $resumen[$clave] === 0 ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ number_format($resumen[$clave]) }}
                                    @if ($clave === 'documentos' && $resumen[$clave] > 0)
                                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ __('y sus archivos') }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach

                        <div class="flex items-baseline justify-between gap-4 py-2">
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Dimensiones perfiladas') }}</dt>
                            <dd class="font-medium tabular-nums {{ $resumen['puntuaciones'] === 0 ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ $resumen['puntuaciones'] }} / {{ count(\App\Enums\DimensionPerfilamientoEnum::cases()) }}
                            </dd>
                        </div>

                        <div class="flex items-baseline justify-between gap-4 py-2">
                            <dt class="text-gray-600 dark:text-gray-400">{{ __('Firmas recogidas') }}</dt>
                            <dd class="font-medium tabular-nums {{ $resumen['firmas'] === 0 ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-gray-100' }}">
                                {{ number_format($resumen['firmas']) }}
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-4 text-sm text-gray-700 dark:text-gray-300">
                        {{ __('También se borrarán la dirección, el teléfono y la fecha del diagnóstico.') }}
                    </p>

                    <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">
                        {{ __('No se puede deshacer. No queda copia de nada de esto.') }}
                    </p>

                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('El infocentro no se borra: seguirá en el catálogo con su código y su nombre, y volverá a la lista de pendientes.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/30">
                    <a href="{{ route('admin.fichas.index') }}"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        {{ __('Cancelar') }}
                    </a>

                    {{-- El botón destructivo va a la derecha porque es el que más
                         cuesta pulsar por descuido, y el que parece el seguro es
                         el de cancelar. --}}
                    <form method="POST" action="{{ route('admin.fichas.destroy', $infocentro) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Sí, eliminar la ficha') }}
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-4 text-sm">
                <a href="{{ route('admin.fichas.show', $infocentro) }}" class="text-gray-600 underline hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                    {{ __('Volver a la ficha sin eliminarla') }}
                </a>
            </p>
        </div>
    </div>
</x-app-layout>