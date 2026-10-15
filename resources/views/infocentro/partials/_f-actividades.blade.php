<section aria-labelledby="seccion-f">
    <h3 id="seccion-f" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección F. Actividades y oferta formativa') }}
    </h3>

    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Marque las actividades que el infocentro desarrolla actualmente. Para cada una, indique frecuencia y número aproximado de participantes.') }}
    </p>

    <div class="mt-4 space-y-4">
        @foreach (\App\Enums\TipoActividadEnum::cases() as $tipo)
            @php
                $fila = $actividades[$tipo->value];
                $frecuenciaActual = old("actividades.frecuencia.{$tipo->value}", $fila['frecuencia']);
                $nivelActual = old("actividades.opcion_nivel.{$tipo->value}", $fila['opcion_nivel']);
                $participantesActual = old("actividades.participantes.{$tipo->value}", $fila['participantes']);
                $detalleActual = old("actividades.detalle.{$tipo->value}", $fila['detalle']);
                // Alpine necesita las comillas: sin ellas `actividades[robotica]`
                // sería una variable inexistente, no una clave del objeto.
                $modeloActividad = "actividades['{$tipo->value}']";
            @endphp

            <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $tipo->label() }}
                </legend>

                <x-si-no
                        :name="'actividades[activa][' . $tipo->value . ']'"
                        :id="'actividad_activa_' . $tipo->value"
                        :pregunta="__('¿El infocentro desarrolla esta actividad?')"
                        :model="$modeloActividad"
                        :valor="$fila['activa']"
                        :error="'actividades.activa.' . $tipo->value"
                    />

                <div x-show="actividades['{{ $tipo->value }}']" x-cloak class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :for="'frecuencia_' . $tipo->value" :value="__('Frecuencia')" />
                        <x-select-input :id="'frecuencia_' . $tipo->value" name="actividades[frecuencia][{{ $tipo->value }}]" class="mt-1 block w-full">
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach (\App\Enums\FrecuenciaEnum::options() as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($frecuenciaActual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('actividades.frecuencia.' . $tipo->value)" />
                    </div>

                    <div>
                        <x-input-label :for="'participantes_' . $tipo->value" :value="__('Participantes aproximados')" />
                        <x-text-input :id="'participantes_' . $tipo->value" name="actividades[participantes][{{ $tipo->value }}]" type="number" min="0" class="mt-1 block w-full"
                            :value="$participantesActual" />
                        <x-input-error class="mt-2" :messages="$errors->get('actividades.participantes.' . $tipo->value)" />
                    </div>

                    <div>
                        <x-input-label :for="'detalle_' . $tipo->value" :value="$tipo->etiquetaDetalle()" />
                        <x-text-input :id="'detalle_' . $tipo->value" name="actividades[detalle][{{ $tipo->value }}]" type="text" class="mt-1 block w-full"
                            :value="$detalleActual" />
                        <x-input-error class="mt-2" :messages="$errors->get('actividades.detalle.' . $tipo->value)" />
                    </div>

                    @if ($tipo->tieneOpcionBinaria())
                        <div>
                            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ $tipo->etiquetaOpcionBinaria() }}</span>
                            <input type="hidden" name="actividades[opcion_si_no][{{ $tipo->value }}]" value="0" />
                            <label class="mt-2 inline-flex items-center gap-2">
                                <input type="checkbox" name="actividades[opcion_si_no][{{ $tipo->value }}]" value="1"
                                    class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm"
                                    @checked(old("actividades.opcion_si_no.{$tipo->value}", $fila['opcion_si_no'])) />
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Sí') }}</span>
                            </label>
                        </div>
                    @endif

                    @if ($tipo->tieneNivel())
                        <div>
                            <x-input-label :for="'nivel_' . $tipo->value" :value="__('Nivel')" />
                            <x-select-input :id="'nivel_' . $tipo->value" name="actividades[opcion_nivel][{{ $tipo->value }}]" class="mt-1 block w-full">
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach (\App\Enums\NivelOfimaticaEnum::options() as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($nivelActual === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                    @endif
                </div>
            </fieldset>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="oferta_formativa_anual" :value="__('F.10. ¿Tiene una oferta formativa planificada anualmente?')" />
            @php($ofertaActual = old('oferta_formativa_anual', $infocentro->oferta_formativa_anual?->value))
            <x-select-input id="oferta_formativa_anual" name="oferta_formativa_anual" class="mt-1 block w-full" x-model="ofertaAnual">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\OfertaAnualEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($ofertaActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('oferta_formativa_anual')" />
        </div>

        <div x-show="ofertaAnualDefinida" x-cloak>
            <x-input-label for="oferta_formativa_define" :value="__('¿Quién la define?')" />
            <x-text-input id="oferta_formativa_define" name="oferta_formativa_define" type="text" class="mt-1 block w-full" :value="old('oferta_formativa_define', $infocentro->oferta_formativa_define)" />
            <x-input-error class="mt-2" :messages="$errors->get('oferta_formativa_define')" />
        </div>
    </div>

    <div class="mt-6">
        <x-input-label for="observaciones_actividades" :value="__('F.11. Observaciones sobre actividades')" />
        <textarea id="observaciones_actividades" name="observaciones_actividades" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('observaciones_actividades', $infocentro->observaciones_actividades) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('observaciones_actividades')" />
    </div>
</section>
