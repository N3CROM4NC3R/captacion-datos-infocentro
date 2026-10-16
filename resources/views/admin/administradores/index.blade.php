<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Administradores generales') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Cuentas con acceso al panel, sin permiso para gestionar administradores.') }}
                </p>
            </div>

            <a href="{{ route('admin.administradores.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                {{ __('Nuevo administrador') }}
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

            {{-- `eliminarAdministrador` envuelve la tabla y el modal: el detalle
                 de cada fila llega en `$detalle`, indexado por id, para no dejar
                 quince copias del marcado del modal en el HTML. --}}
            <div x-data="eliminarAdministrador(@js($detalle), @js(rtrim(route('admin.administradores.index'), '/') . '/'))"
                x-on:abrir-borrar-admin.window="abrir($event.detail)">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Nombre') }}</th>
                                    <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Usuario') }}</th>
                                    <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Correo') }}</th>
                                    <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Alta') }}</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($administradores as $administrador)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $administrador->name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $administrador->username }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $administrador->email }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $administrador->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-end text-sm">
                                            <div class="flex items-center justify-end gap-4">
                                                <a href="{{ route('admin.administradores.edit', $administrador) }}" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                    {{ __('Editar') }}
                                                </a>

                                                {{-- Ni técnico ni facilitador aparecen en el listado,
                                                     pero el botón se guarda por si alguien llega
                                                     escribiendo la URL de un borrado a mano. --}}
                                                @unless ($administrador->is($tecnico))
                                                    <button type="button"
                                                        @click="$dispatch('abrir-borrar-admin', {{ $administrador->id }})"
                                                        class="text-sm text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                        {{ __('Eliminar') }}
                                                    </button>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Todavía no hay administradores generales.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-modal name="eliminar-administrador" :max-width="'lg'">
                    {{-- `x-if` y no `x-show`: hasta que no se elige fila, el
                         `action` del formulario no tiene a quién apuntar y
                         renderizarlo sería publicar un DELETE sin destino. --}}
                    <template x-if="objetivo">
                        <div>
                            <div class="px-6 pt-6">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('Eliminar administrador') }}
                                </h2>

                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-gray-900 dark:text-gray-100"
                                        x-text="objetivo.nombre"></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400"
                                        x-text="objetivo.username"></span>
                                </p>
                            </div>

                            <div class="px-6 py-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Esta cuenta se va a perder y ya no podrá entrar al panel con ella.') }}
                                </p>

                                <p class="mt-4 rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-900/40 dark:text-red-200">
                                    {{ __('No se puede deshacer.') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3 bg-gray-50 px-6 py-4 dark:bg-gray-900/30">
                                <a href="{{ route('admin.administradores.index') }}"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    {{ __('Cancelar') }}
                                </a>

                                <form method="POST" :action="action" autocomplete="off">
                                    @csrf
                                    @method('DELETE')
                                    <x-danger-button>
                                        {{ __('Sí, eliminar') }}
                                    </x-danger-button>
                                </form>
                            </div>
                        </div>
                    </template>
                </x-modal>
            </div>

            <div class="mt-4">
                {{ $administradores->links() }}
            </div>
        </div>
    </div>
</x-app-layout>