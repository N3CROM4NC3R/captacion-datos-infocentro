<section aria-labelledby="seccion-b">
    <h3 id="seccion-b" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección B. Conectividad') }}
    </h3>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="servicio_internet" :value="__('B.1. ¿El infocentro cuenta con servicio de internet?')" />
            @php($servicioActual = old('servicio_internet', $infocentro->servicio_internet?->value))
            <x-select-input id="servicio_internet" name="servicio_internet" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\ServicioInternetEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($servicioActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('servicio_internet')" />
        </div>

        <div>
            <x-input-label for="proveedor_internet" :value="__('B.2. ¿Qué empresa provee el servicio?')" />
            @php($proveedorActual = old('proveedor_internet', $infocentro->proveedor_internet?->value))
            <x-select-input id="proveedor_internet" name="proveedor_internet" class="mt-1 block w-full" x-model="proveedor">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\ProveedorInternetEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($proveedorActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('proveedor_internet')" />
        </div>

        <div x-show="proveedorNecesitaNombre" x-cloak>
            <x-input-label for="proveedor_nombre" :value="__('¿Cuál?')" />
            <x-text-input id="proveedor_nombre" name="proveedor_nombre" type="text" class="mt-1 block w-full" :value="old('proveedor_nombre', $infocentro->proveedor_nombre)" />
            <x-input-error class="mt-2" :messages="$errors->get('proveedor_nombre')" />
        </div>

        <div x-show="proveedor === 'cantv'" x-cloak>
            <x-input-label for="proveedor_detalle" :value="__('Tipo de servicio de CANTV')" />
            @php($detalleActual = old('proveedor_detalle', $infocentro->proveedor_detalle?->value))
            <x-select-input id="proveedor_detalle" name="proveedor_detalle" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\TipoServicioCantvEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($detalleActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('proveedor_detalle')" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="observaciones_conectividad" :value="__('B.3. Observaciones sobre conectividad')" />
            <textarea id="observaciones_conectividad" name="observaciones_conectividad" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('observaciones_conectividad', $infocentro->observaciones_conectividad) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('observaciones_conectividad')" />
        </div>
    </div>
</section>
