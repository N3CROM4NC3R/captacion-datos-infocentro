@php
    /**
     * Los campos del alta y de la edición.
     *
     * Se incluyen en los dos formularios porque son los mismos datos; lo único
     * que cambia entre alta y corrección es si la contraseña es obligatoria, y
     * eso lo pone la etiqueta.
     *
     * @var bool|null  $administrador  null al dar de alta; el usuario al corregir
     */
    $passwordObligatoria = ! isset($administrador);
@endphp

<div>
    <x-input-label for="name" :value="__('Nombre')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $administrador->name ?? '')" required autofocus autocomplete="name" />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="username" :value="__('Usuario')" />
    <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $administrador->username ?? '')" required autocomplete="username" />
    <x-input-error class="mt-2" :messages="$errors->get('username')" />
</div>

<div>
    <x-input-label for="email" :value="__('Correo')" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $administrador->email ?? '')" required autocomplete="email" />
    <x-input-error class="mt-2" :messages="$errors->get('email')" />
</div>

<div>
    <x-input-label for="password" :value="$passwordObligatoria ? __('Contraseña') : __('Contraseña (opcional)')" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="$passwordObligatoria" autocomplete="new-password" />
    @unless ($passwordObligatoria)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('Déjala vacía para no cambiarla.') }}
        </p>
    @endunless
    <x-input-error class="mt-2" :messages="$errors->get('password')" />
</div>

<div>
    <x-input-label for="password_confirmation" :value="__('Repite la contraseña')" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="$passwordObligatoria" autocomplete="new-password" />
    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
</div>