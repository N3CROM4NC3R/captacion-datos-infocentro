<section aria-labelledby="seccion-a">
    <h3 id="seccion-a" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección A. Identificación del infocentro') }}
    </h3>

    <dl class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-700/40 p-4 flex">
        <div class="mx-5">
            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Código') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $infocentro->codigo }}</dd>
        </div>
        <div class="mx-5">
            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Nombre') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $infocentro->nombre }}</dd>
        </div>
        <div class="mx-5">
            <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Estatus') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $infocentro->activo?"Activo":"Inactivo" }}</dd>
        </div>
    </dl>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="estado_id" :value="__('A.1. Estado')" />
            <x-select-input id="estado_id" name="estado_id" class="mt-1 block w-full" x-model="estadoId" @change="cargarMunicipios()">
                <option value="">{{ __('Seleccione un estado') }}</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado->id }}" @selected((string) $estadoId === (string) $estado->id)>{{ $estado->nombre }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('estado_id')" />
        </div>

        <div>
            <x-input-label for="municipio_id" :value="__('A.2. Municipio')" />
            <x-select-input id="municipio_id" name="municipio_id" class="mt-1 block w-full" x-model="municipioId" @change="cargarParroquias()" x-bind:disabled="!estadoId">
                <option value="">{{ __('Seleccione un municipio') }}</option>
                <template x-for="(nombre, id) in municipios" :key="id">
                    <option :value="id" x-text="nombre"></option>
                </template>
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('municipio_id')" />
        </div>

        <div>
            <x-input-label for="parroquia_id" :value="__('A.3. Parroquia')" />
            <x-select-input id="parroquia_id" name="parroquia_id" class="mt-1 block w-full" x-model="parroquiaId" x-bind:disabled="!municipioId">
                <option value="">{{ __('Seleccione una parroquia') }}</option>
                <template x-for="(nombre, id) in parroquias" :key="id">
                    <option :value="id" x-text="nombre"></option>
                </template>
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('parroquia_id')" />
        </div>

        <div>
            <x-input-label for="comuna_circuito" :value="__('A.4. Comuna / Circuito Comunal')" />
            <x-text-input id="comuna_circuito" name="comuna_circuito" type="text" class="mt-1 block w-full" :value="old('comuna_circuito', $infocentro->comuna_circuito)" />
            <x-input-error class="mt-2" :messages="$errors->get('comuna_circuito')" />
        </div>

        <div>
            <x-input-label for="consejo_comunal" :value="__('A.5. Consejo Comunal (nombre)')" />
            <x-text-input id="consejo_comunal" name="consejo_comunal" type="text" class="mt-1 block w-full" :value="old('consejo_comunal', $infocentro->consejo_comunal)" />
            <x-input-error class="mt-2" :messages="$errors->get('consejo_comunal')" />
        </div>

        <div>
            <x-input-label for="sala_autogobierno_comunal" :value="__('A.6. Sala de Autogobierno Comunal (nombre)')" />
            <x-text-input id="sala_autogobierno_comunal" name="sala_autogobierno_comunal" type="text" class="mt-1 block w-full" :value="old('sala_autogobierno_comunal', $infocentro->sala_autogobierno_comunal)" />
            <x-input-error class="mt-2" :messages="$errors->get('sala_autogobierno_comunal')" />
        </div>

        <div>
            <x-input-label for="nombre" :value="__('A.7. Nombre del Infocentro')" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $infocentro->nombre)" required />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        <div>
            <x-input-label for="direccion" :value="__('A.8. Dirección exacta')" />
            <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full" :value="old('direccion', $infocentro->direccion)" />
            <x-input-error class="mt-2" :messages="$errors->get('direccion')" />
        </div>

        <div>
            <x-input-label for="telefono" :value="__('A.9. Teléfono de contacto')" />
            <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $infocentro->telefono)" />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <x-input-label for="activo" :value="__('A.10. Estatus')" />
            <x-select-input id="activo" name="activo" class="mt-1 block w-full" x-model="activo">
                <option value="1">{{ __('Activo') }}</option>
                <option value="0">{{ __('Inactivo') }}</option>
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('activo')" />
        </div>

        <div x-show="activo === '0'" x-cloak>
            <x-input-label for="razon_cierre" :value="__('A.11. Razón del estatus inactivo')" />
            <x-text-input id="razon_cierre" name="razon_cierre" type="text" class="mt-1 block w-full" :value="old('razon_cierre', $infocentro->razon_cierre)" />
            <x-input-error class="mt-2" :messages="$errors->get('razon_cierre')" />
        </div>

        <div>
            <x-input-label for="latitud" :value="__('A.12. Coordenadas GPS — Latitud')" />
            <x-text-input id="latitud" name="latitud" type="number" step="any" min="-90" max="90" class="mt-1 block w-full" :value="old('latitud', $infocentro->latitud)" />
            <x-input-error class="mt-2" :messages="$errors->get('latitud')" />
        </div>

        <div>
            <x-input-label for="longitud" :value="__('A.13. Coordenadas GPS — Longitud')" />
            <x-text-input id="longitud" name="longitud" type="number" step="any" min="-180" max="180" class="mt-1 block w-full" :value="old('longitud', $infocentro->longitud)" />
            <x-input-error class="mt-2" :messages="$errors->get('longitud')" />
        </div>

        <div>
            <x-input-label for="fecha_diagnostico" :value="__('A.14. Fecha del diagnóstico')" />
            <x-text-input id="fecha_diagnostico" name="fecha_diagnostico" type="date" class="mt-1 block w-full" :value="old('fecha_diagnostico', $infocentro->fecha_diagnostico?->format('Y-m-d'))" />
            <x-input-error class="mt-2" :messages="$errors->get('fecha_diagnostico')" />
        </div>

        <div>
            <x-input-label for="nombre_diagnosticador" :value="__('A.15. Nombre del diagnosticador')" />
            <x-text-input id="nombre_diagnosticador" name="nombre_diagnosticador" type="text" class="mt-1 block w-full" :value="old('nombre_diagnosticador', $infocentro->nombre_diagnosticador)" />
            <x-input-error class="mt-2" :messages="$errors->get('nombre_diagnosticador')" />
        </div>

        <div>
            <x-input-label for="cargo_institucion" :value="__('A.16. Cargo / Institución')" />
            <x-text-input id="cargo_institucion" name="cargo_institucion" type="text" class="mt-1 block w-full" :value="old('cargo_institucion', $infocentro->cargo_institucion)" />
            <x-input-error class="mt-2" :messages="$errors->get('cargo_institucion')" />
        </div>
    </div>
</section>
