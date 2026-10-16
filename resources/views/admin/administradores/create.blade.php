<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Nuevo administrador general') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Tendrá acceso a todo el panel menos a la gestión de administradores.') }}
                </p>
            </div>

            <a href="{{ route('admin.administradores.index') }}" class="text-sm text-gray-600 hover:underline dark:text-gray-300">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.administradores.store') }}" class="space-y-6">
                        @csrf

                        @include('admin.administradores._form')

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.administradores.index') }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                {{ __('Cancelar') }}
                            </a>

                            <x-primary-button>{{ __('Crear') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>