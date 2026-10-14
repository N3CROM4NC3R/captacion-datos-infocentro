<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentros registrados') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $infocentrosCount }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentros activos') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $activeInfocentrosCount }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
