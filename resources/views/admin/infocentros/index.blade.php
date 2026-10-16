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
                <p class="text-gray-600 dark:text-gray-300">
                    <span class="font-semibold">{{ number_format($conFicha) }}</span>
                    {{ __('de') }}
                    <span class="font-semibold">{{ number_format($total) }}</span>
                    {{ __('infocentros tienen la ficha de diagnóstico guardada.') }}
                </p>
                <a href="{{ route('admin.fichas.index') }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                    {{ __('Ver las fichas capturadas') }}
                </a>
            </div>

            <form method="GET" action="{{ route('admin.infocentros.index') }}" class="mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                <div>
                    <x-input-label for="q" :value="__('Buscar por nombre o código')" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$busqueda" :placeholder="__('Nombre o código…')" />
                </div>

                <div>
                    <x-input-label for="ficha" :value="__('Estado de la ficha')" />
                    <x-select-input id="ficha" name="ficha" class="mt-1 block w-full">
                        <option value="">{{ __('Todas') }}</option>
                        <option value="hecha" @selected($ficha === 'hecha')>{{ __('Ficha guardada') }}</option>
                        <option value="pendiente" @selected($ficha === 'pendiente')>{{ __('Sin ficha') }}</option>
                    </x-select-input>
                </div>

                <div class="flex gap-2">
                    <x-primary-button>{{ __('Filtrar') }}</x-primary-button>
                    @if ($busqueda !== '' || $ficha !== '')
                        <a href="{{ route('admin.infocentros.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-600 hover:underline dark:text-gray-300">
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
                                        <a href="{{ route('admin.infocentros.edit', $infocentro) }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                            {{ __('Editar') }}
                                        </a>
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

            <div class="mt-4">
                {{ $infocentros->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
