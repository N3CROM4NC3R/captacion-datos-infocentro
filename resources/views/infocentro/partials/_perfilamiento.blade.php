<section aria-labelledby="perfilamiento" x-data="perfilamiento()">
    <h3 id="perfilamiento" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Perfilamiento y clasificación del infocentro') }}
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ __('Esta sección debe ser llenada por el diagnosticador con base en la información recabada en las secciones anteriores.') }}
    </p>

    <div class="mt-4">
        <x-input-label :value="__('J.1. Puntuación por dimensiones (asigne 0 a 3 puntos por dimensión)')" />

        <div class="mt-3 space-y-4">
            @foreach (\App\Enums\DimensionPerfilamientoEnum::cases() as $dimension)
                @php
                    $campo = 'puntuaciones.'.$dimension->value;
                    $actual = old($campo, $puntuaciones[$dimension->value] ?? '');
                @endphp

                <div class="grid gap-2 sm:grid-cols-3 sm:items-center">
                    <x-input-label
                        :for="'puntuacion_'.$dimension->value"
                        :value="$dimension->label()"
                        class="sm:font-normal"
                    />

                    <x-select-input
                        :id="'puntuacion_'.$dimension->value"
                        :name="'puntuaciones['.$dimension->value.']'"
                        class="block w-full sm:col-span-2"
                        x-model="{{ $dimension->propiedadAlpine() }}"
                        x-on:change="recalcular()"
                    >
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($dimension->opciones() as $puntos => $opcion)
                            <option value="{{ $puntos }}" @selected((string) $actual === (string) $puntos)>{{ $opcion }}</option>
                        @endforeach
                    </x-select-input>
                </div>

                <x-input-error class="mt-1" :messages="$errors->get($campo)" />
            @endforeach
        </div>

        <div class="mt-4 rounded-md bg-gray-50 dark:bg-gray-900/50 px-4 py-3 flex items-baseline justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Total') }}</span>
            <span class="text-sm text-gray-700 dark:text-gray-300">
                <span class="text-lg font-semibold tabular-nums" x-text="total"></span>
                <span>/ {{ \App\Enums\DimensionPerfilamientoEnum::TOTAL_MAXIMO }}</span>
            </span>
        </div>
    </div>

    <div class="mt-6">
        <x-input-label :value="__('J.2. Clasificación del infocentro')" />

        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ __('La clasificación se deduce del total de J.1 y no se elige.') }}
        </p>

        <div class="mt-3 space-y-3">
            @foreach (\App\Enums\TipoInfocentroEnum::cases() as $tipo)
                {{-- Las clases se arman aquí y no en un :class multilínea: Blade
                     lee el valor de un atributo hasta la primera comilla doble,
                     así que un ternario partido en varias líneas rompe la
                     etiqueta y Alpine descarta el resto de la sección. --}}
                @php
                    $seleccionado = 'border-indigo-500 bg-indigo-50/60 dark:border-indigo-400 dark:bg-indigo-900/20';
                    $sinSeleccionar = 'border-gray-200 dark:border-gray-700';
                    $puntoLleno = 'border-indigo-600 bg-indigo-600 text-white';
                    $puntoVacio = 'border-gray-300 dark:border-gray-600';
                @endphp

                <div
                    class="rounded-lg border p-4 transition-colors"
                    :class="tipoActual === '{{ $tipo->value }}' ? '{{ $seleccionado }}' : '{{ $sinSeleccionar }}'"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                            :class="tipoActual === '{{ $tipo->value }}' ? '{{ $puntoLleno }}' : '{{ $puntoVacio }}'"
                            x-bind:aria-hidden="tipoActual === '{{ $tipo->value }}' ? 'false' : 'true'"
                        >
                            <svg x-show="tipoActual === '{{ $tipo->value }}'" x-cloak class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                                <path d="M2 6.5l2.5 2.5L10 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>

                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $tipo->label() }}
                                <span class="font-normal text-gray-500 dark:text-gray-400">({{ $tipo->rango() }})</span>
                            </p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $tipo->descripcion() }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">{{ __('Intervención:') }}</span> {{ $tipo->intervencion() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6">
        <x-input-label for="prioridad_intervencion" :value="__('J.3. Prioridad de intervención')" />
        @php($prioridadActual = old('prioridad_intervencion', $infocentro->prioridad_intervencion?->value))
        <x-select-input id="prioridad_intervencion" name="prioridad_intervencion" class="mt-1 block w-full">
            <option value="">{{ __('Seleccione') }}</option>
            @foreach (\App\Enums\PrioridadIntervencionEnum::options() as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected($prioridadActual === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </x-select-input>
        <x-input-error class="mt-2" :messages="$errors->get('prioridad_intervencion')" />
    </div>

    <div class="mt-6">
        <x-input-label for="recomendaciones" :value="__('J.4. Recomendaciones específicas para este infocentro')" />
        <textarea
            id="recomendaciones"
            name="recomendaciones"
            rows="4"
            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
        >{{ old('recomendaciones', $infocentro->recomendaciones) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('recomendaciones')" />
    </div>
</section>
