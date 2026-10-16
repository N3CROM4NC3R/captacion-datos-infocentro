<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Tu cuenta no tiene permisos asignados') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Tu cuenta de administrador existe, pero todavía no se le ha concedido ningún permiso sobre los infocentros ni sus fichas.') }}
                    </p>

                    <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        {{ __('Para poder trabajar, pide al administrador técnico que te conceda al menos el permiso de ver el catálogo.') }}
                    </p>

                    <div class="mt-5 rounded-md bg-gray-50 px-4 py-3 text-sm dark:bg-gray-700/40">
                        <p class="font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Mientras tanto puedes:') }}
                        </p>
                        <ul class="mt-2 list-inside list-disc text-gray-600 dark:text-gray-400">
                            <li>{{ __('Actualizar tu contraseña.') }}</li>
                            <li>{{ __('Salir de la sesión.') }}</li>
                        </ul>

                        <div class="mt-3 flex gap-3">
                            <a href="{{ route('profile.edit') }}"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ __('Mi perfil') }}
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    {{ __('Cerrar sesión') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>