@php
    /** @var \App\Models\Infocentro|null $infocentro */
    $infocentro = $infocentro ?? null;
    $user = $infocentro?->user;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Infocentro --}}
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Datos del infocentro') }}</h2>
        </header>

        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $infocentro?->nombre)" required autofocus />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div>
            <x-input-label for="codigo" :value="__('Código')" />
            <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full" :value="old('codigo', $infocentro?->codigo)" required />
            <x-input-error class="mt-2" :messages="$errors->get('codigo')" />
        </div>

        <div>
            <x-input-label for="direccion" :value="__('Dirección')" />
            <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full" :value="old('direccion', $infocentro?->direccion)" />
            <x-input-error class="mt-2" :messages="$errors->get('direccion')" />
        </div>

        <div>
            <x-input-label for="telefono" :value="__('Teléfono')" />
            <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $infocentro?->telefono)" />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <label for="activo" class="inline-flex items-center">
                <input id="activo" type="checkbox" name="activo" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" @checked(old('activo', $infocentro?->activo ?? true)) />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Infocentro activo') }}</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('activo')" />
        </div>
    </section>

    {{-- User --}}
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Usuario del infocentro') }}</h2>
        </header>

        <div>
            <x-input-label for="user_name" :value="__('Nombre')" />
            <x-text-input id="user_name" name="user_name" type="text" class="mt-1 block w-full" :value="old('user_name', $user?->name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('user_name')" />
        </div>

        <div>
            <x-input-label for="user_username" :value="__('Usuario')" />
            <x-text-input id="user_username" name="user_username" type="text" class="mt-1 block w-full" :value="old('user_username', $user?->username)" required autocomplete="off" />
            <x-input-error class="mt-2" :messages="$errors->get('user_username')" />
        </div>

        <div>
            <x-input-label for="user_email" :value="__('Correo electrónico')" />
            <x-text-input id="user_email" name="user_email" type="email" class="mt-1 block w-full" :value="old('user_email', $user?->email)" required />
            <x-input-error class="mt-2" :messages="$errors->get('user_email')" />
        </div>

        <div>
            <x-input-label for="user_password" :value="$user ? __('Nueva contraseña') : __('Contraseña')" />
            <x-text-input id="user_password" name="user_password" type="password" class="mt-1 block w-full" :required="! $user" autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('user_password')" />
            @if ($user)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Déjalo en blanco para mantener la contraseña actual.') }}</p>
            @endif
        </div>

        <div>
            <x-input-label for="user_password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="user_password_confirmation" name="user_password_confirmation" type="password" class="mt-1 block w-full" :required="! $user" autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('user_password_confirmation')" />
        </div>
    </section>
</div>
