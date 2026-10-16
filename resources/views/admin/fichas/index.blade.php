<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Fichas de diagnóstico') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ number_format($totalFichas) }} {{ __('de') }} {{ number_format(\App\Models\Infocentro::count()) }}
                    {{ __('infocentros con la ficha guardada.') }}
                </p>
            </div>

            <a href="{{ route('admin.infocentros.index') }}" class="text-sm text-gray-600 hover:underline dark:text-gray-300">
                {{ __('Volver a infocentros') }}
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

            <div class="mb-4 grid gap-3 sm:grid-cols-4">
                @foreach (\App\Enums\TipoInfocentroEnum::cases() as $caso)
                    <div @class([
                        'rounded-lg border px-4 py-3',
                        'border-indigo-400 bg-indigo-50/60 dark:border-indigo-500 dark:bg-indigo-900/20' => $tipo === $caso->value,
                        'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' => $tipo !== $caso->value,
                    ])>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $caso->label() }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $caso->rango() }}</p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                            {{ number_format($porTipo[$caso->value] ?? 0) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.fichas.index') }}" class="mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                <div>
                    <x-input-label for="q" :value="__('Buscar por nombre o código')" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$busqueda" :placeholder="__('Nombre o código…')" />
                </div>

                <div>
                    <x-input-label for="tipo" :value="__('Tipo')" />
                    <x-select-input id="tipo" name="tipo" class="mt-1 block w-full">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach (\App\Enums\TipoInfocentroEnum::options() as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($tipo === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <div>
                    <x-input-label for="prioridad" :value="__('Prioridad de intervención')" />
                    <x-select-input id="prioridad" name="prioridad" class="mt-1 block w-full">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach (\App\Enums\PrioridadIntervencionEnum::options() as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected($prioridad === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <div class="flex gap-2">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    @if ($busqueda !== '' || $tipo !== '' || $prioridad !== '')
                        <a href="{{ route('admin.fichas.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:underline dark:text-gray-300">
                            {{ __('Limpiar') }}
                        </a>
                    @endif
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Infocentro') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Territorio') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Total') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Tipo') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Prioridad') }}</th>
                                <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Guardada') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($fichas as $ficha)
                                @php
                                    $colores = [
                                        'a' => 'text-green-600 dark:text-green-400',
                                        'b' => 'text-amber-600 dark:text-amber-400',
                                        'c' => 'text-red-600 dark:text-red-400',
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 text-sm">
                                        <span class="block whitespace-nowrap text-gray-900 dark:text-gray-100">{{ $ficha->nombre }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $ficha->codigo }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300"
                                        title="{{ collect([$ficha->estado?->nombre, $ficha->municipio?->nombre])->filter()->implode(' · ') }}">
                                        <span class="block max-w-40 truncate">{{ collect([$ficha->estado?->nombre, $ficha->municipio?->nombre])->filter()->implode(' · ') ?: '—' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm tabular-nums text-gray-900 dark:text-gray-100">
                                        {{ $ficha->puntuacion_total ?? '—' }}<span class="text-gray-400">/21</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm {{ $colores[$ficha->tipo_infocentro?->value] ?? 'text-gray-400' }}">
                                        {{ $ficha->tipo_infocentro?->label() ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $ficha->prioridad_intervencion?->label() ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $ficha->diagnostico_guardado_at->format('d/m/Y') }}
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                                            act. {{ $ficha->diagnostico_actualizado_at?->format('d/m/Y H:i') ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-end text-sm">
                                        <a href="{{ route('admin.fichas.show', $ficha) }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                            {{ __('Ver la ficha') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Todavía no se ha guardado ninguna ficha de diagnóstico.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $fichas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
