<section aria-labelledby="seccion-c">
    <h3 id="seccion-c" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección C. Operatividad') }}
    </h3>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="operatividad" :value="__('C.1. ¿El infocentro está operativo actualmente?')" />
            @php($operatividadActual = old('operatividad', $infocentro->operatividad?->value))
            <x-select-input id="operatividad" name="operatividad" class="mt-1 block w-full" x-model="estadoOperativo">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\OperatividadEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($operatividadActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('operatividad')" />
        </div>

        <div x-show="operatividadNecesitaDetalle" x-cloak>
            <x-input-label for="operatividad_detalle" :value="__('Especifique')" />
            <x-text-input id="operatividad_detalle" name="operatividad_detalle" type="text" class="mt-1 block w-full" :value="old('operatividad_detalle', $infocentro->operatividad_detalle)" />
            <x-input-error class="mt-2" :messages="$errors->get('operatividad_detalle')" />
        </div>
    </div>

    <div class="mt-6">
        <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('C.2. Horario de atención actual — días') }}</span>

        <div class="mt-2 flex flex-wrap gap-4">
            @foreach ($dias as $valor => $etiqueta)
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="dias_atencion[]" value="{{ $valor }}"
                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        @checked(in_array($valor, old('dias_atencion', $infocentro->dias_atencion ?? []), true)) />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $etiqueta }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('dias_atencion')" />
    </div>

    <div class="mt-4 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="horario_inicio" :value="__('Horario desde')" />
            <x-text-input id="horario_inicio" name="horario_inicio" type="time" class="mt-1 block w-full" :value="old('horario_inicio', $infocentro->horario_inicio?->format('H:i'))" />
            <x-input-error class="mt-2" :messages="$errors->get('horario_inicio')" />
        </div>

        <div>
            <x-input-label for="horario_fin" :value="__('Horario hasta')" />
            <x-text-input id="horario_fin" name="horario_fin" type="time" class="mt-1 block w-full" :value="old('horario_fin', $infocentro->horario_fin?->format('H:i'))" />
            <x-input-error class="mt-2" :messages="$errors->get('horario_fin')" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="horario_suficiente" :value="__('C.3. ¿El horario responde a las necesidades de la comunidad?')" />
            @php($horarioActual = old('horario_suficiente', $infocentro->horario_suficiente?->value))
            <x-select-input id="horario_suficiente" name="horario_suficiente" class="mt-1 block w-full" x-model="horarioSuficiente">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\SuficienciaHorarioEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($horarioActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('horario_suficiente')" />
        </div>

        <div x-show="horarioNecesitaMotivo" x-cloak>
            <x-input-label for="horario_suficiente_motivo" :value="__('¿Por qué?')" />
            <x-text-input id="horario_suficiente_motivo" name="horario_suficiente_motivo" type="text" class="mt-1 block w-full" :value="old('horario_suficiente_motivo', $infocentro->horario_suficiente_motivo)" />
            <x-input-error class="mt-2" :messages="$errors->get('horario_suficiente_motivo')" />
        </div>

        <div>
            <x-input-label for="condicion_espacio" :value="__('C.4. ¿El espacio físico está en condiciones adecuadas?')" />
            @php($condicionActual = old('condicion_espacio', $infocentro->condicion_espacio?->value))
            <x-select-input id="condicion_espacio" name="condicion_espacio" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\CondicionEspacioEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($condicionActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('condicion_espacio')" />
        </div>
    </div>

    <div class="mt-6">
        <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">
            {{ __('C.5. Estado de las instalaciones (marque todas las que apliquen)') }}
        </span>

        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach ($instalaciones as $valor => $etiqueta)
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="instalaciones[]" value="{{ $valor }}"
                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        @checked(in_array($valor, old('instalaciones', $infocentro->instalaciones ?? []), true)) />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $etiqueta }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('instalaciones')" />
    </div>

    <div class="mt-6">
        <x-input-label for="observaciones_operatividad" :value="__('C.6. Observaciones sobre operatividad')" />
        <textarea id="observaciones_operatividad" name="observaciones_operatividad" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('observaciones_operatividad', $infocentro->observaciones_operatividad) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('observaciones_operatividad')" />
    </div>
</section>
