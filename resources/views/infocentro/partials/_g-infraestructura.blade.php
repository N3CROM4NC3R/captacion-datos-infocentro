<section aria-labelledby="seccion-g">
    <h3 id="seccion-g" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección G. Infraestructura y equipamiento') }}
    </h3>

    <div class="mt-4 space-y-4">
        @foreach (\App\Enums\TipoEquipoEnum::cases() as $tipo)
            @php
                $fila = $equipos[$tipo->value];
                $etiquetaDetalle = $tipo->etiquetaDetalle();
                $estadoActual = old("equipos.estado.{$tipo->value}", $fila['estado']);
                $cantidadActual = old("equipos.cantidad.{$tipo->value}", $fila['cantidad']);
                $funcionandoActual = old("equipos.funcionando.{$tipo->value}", $fila['funcionando']);
                $fueraActual = old("equipos.fuera_servicio.{$tipo->value}", $fila['fuera_servicio']);
                $detalleActual = old("equipos.detalle.{$tipo->value}", $fila['detalle']);
                $formatoActual = old("equipos.formato.{$tipo->value}", $fila['formato']);
                $observacionesActual = old("equipos.observaciones.{$tipo->value}", $fila['observaciones']);
            @endphp

            <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $tipo->label() }}
                </legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :for="'cantidad_' . $tipo->value" :value="__('Cantidad Asignada')" />
                        <x-text-input :id="'cantidad_' . $tipo->value" name="equipos[cantidad][{{ $tipo->value }}]" type="number" min="0" class="mt-1 block w-full"
                            :value="$cantidadActual" />
                        <x-input-error class="mt-2" :messages="$errors->get('equipos.cantidad.' . $tipo->value)" />
                    </div>

                    <div>
                        <x-input-label :for="'estado_' . $tipo->value" :value="__('Estado general')" />
                        <x-select-input :id="'estado_' . $tipo->value" name="equipos[estado][{{ $tipo->value }}]" class="mt-1 block w-full">
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach (\App\Enums\EstadoEquipoEnum::options() as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($estadoActual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('equipos.estado.' . $tipo->value)" />
                    </div>

                    @if ($tipo->tieneDesglose())
                        <div>
                            <x-input-label :for="'funcionando_' . $tipo->value" :value="__('En funcionamiento')" />
                            <x-text-input :id="'funcionando_' . $tipo->value" name="equipos[funcionando][{{ $tipo->value }}]" type="number" min="0" class="mt-1 block w-full"
                                :value="$funcionandoActual" />
                            <x-input-error class="mt-2" :messages="$errors->get('equipos.funcionando.' . $tipo->value)" />
                        </div>

                        <div>
                            <x-input-label :for="'fuera_' . $tipo->value" :value="__('Fuera de servicio')" />
                            <x-text-input :id="'fuera_' . $tipo->value" name="equipos[fuera_servicio][{{ $tipo->value }}]" type="number" min="0" class="mt-1 block w-full"
                                :value="$fueraActual" />
                            <x-input-error class="mt-2" :messages="$errors->get('equipos.fuera_servicio.' . $tipo->value)" />
                        </div>
                    @endif

                    @if ($etiquetaDetalle)
                        <div class="{{ $tipo->tieneDesglose() ? '' : 'sm:col-span-2' }}">
                            <x-input-label :for="'detalle_equipo_' . $tipo->value" :value="$etiquetaDetalle" />
                            <x-text-input :id="'detalle_equipo_' . $tipo->value" name="equipos[detalle][{{ $tipo->value }}]" type="text" class="mt-1 block w-full"
                                :value="$detalleActual" />
                        </div>
                    @endif

                    @if ($tipo->tieneFormato())
                        <div class="{{ $tipo->tieneDesglose() ? '' : 'sm:col-span-2' }}">
                            <x-input-label :for="'formato_' . $tipo->value" :value="__('G.1. ¿Son equipos de escritorio o portátiles?')" />
                            <x-select-input :id="'formato_' . $tipo->value" name="equipos[formato][{{ $tipo->value }}]" class="mt-1 block w-full">
                                <option value="">{{ __('Seleccione') }}</option>
                                @foreach (\App\Enums\FormatoEquipoEnum::options() as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($formatoActual === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </x-select-input>
                            <x-input-error class="mt-2" :messages="$errors->get('equipos.formato.' . $tipo->value)" />
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <x-input-label :for="'obs_equipo_' . $tipo->value" :value="__('Observaciones')" />
                        <x-text-input :id="'obs_equipo_' . $tipo->value" name="equipos[observaciones][{{ $tipo->value }}]" type="text" class="mt-1 block w-full"
                            :value="$observacionesActual" />
                    </div>
                </div>
            </fieldset>
        @endforeach
    </div>

    <h4 class="mt-8 font-medium text-gray-900 dark:text-gray-100">{{ __('G.5. Sistemas de energía') }}</h4>

    <div class="mt-4 space-y-4">
        @foreach (\App\Enums\TipoEnergiaEnum::cases() as $tipo)
            @php
                $fila = $energias[$tipo->value];
                $etiquetaDetalle = $tipo->etiquetaDetalle();
                $estadoActual = old("energias.estado.{$tipo->value}", $fila['estado']);
                $cantidadActual = old("energias.cantidad.{$tipo->value}", $fila['cantidad']);
                $detalleActual = old("energias.detalle.{$tipo->value}", $fila['detalle']);
                $observacionesActual = old("energias.observaciones.{$tipo->value}", $fila['observaciones']);
            @endphp

            <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $tipo->label() }}
                </legend>

                <div class="grid gap-4 sm:grid-cols-3">
                    @if ($tipo->cantidadFija())
                        <div>
                            <x-input-label :for="'cant_energia_' . $tipo->value" :value="__('Cantidad')" />
                            <x-text-input :id="'cant_energia_' . $tipo->value" name="energias[cantidad][{{ $tipo->value }}]" type="number" min="0" class="mt-1 block w-full"
                                :value="$cantidadActual" />
                            <x-input-error class="mt-2" :messages="$errors->get('energias.cantidad.' . $tipo->value)" />
                        </div>
                    @endif

                    <div>
                        <x-input-label :for="'estado_energia_' . $tipo->value" :value="__('Estado')" />
                        <x-select-input :id="'estado_energia_' . $tipo->value" name="energias[estado][{{ $tipo->value }}]" class="mt-1 block w-full">
                            <option value="">{{ __('Seleccione') }}</option>
                            @foreach (\App\Enums\EstadoEquipoEnum::options() as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($estadoActual === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error class="mt-2" :messages="$errors->get('energias.estado.' . $tipo->value)" />
                    </div>

                    @if ($etiquetaDetalle)
                        <div>
                            <x-input-label :for="'detalle_energia_' . $tipo->value" :value="$etiquetaDetalle" />
                            <x-text-input :id="'detalle_energia_' . $tipo->value" name="energias[detalle][{{ $tipo->value }}]" type="text" class="mt-1 block w-full"
                                :value="$detalleActual" />
                        </div>
                    @endif

                    <div class="sm:col-span-3">
                        <x-input-label :for="'obs_energia_' . $tipo->value" :value="__('Observaciones')" />
                        <x-text-input :id="'obs_energia_' . $tipo->value" name="energias[observaciones][{{ $tipo->value }}]" type="text" class="mt-1 block w-full"
                            :value="$observacionesActual" />
                    </div>
                </div>
            </fieldset>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="suficiencia_equipamiento" :value="__('G.7. ¿El equipamiento es suficiente para atender la demanda?')" />
            @php($suficienciaActual = old('suficiencia_equipamiento', $infocentro->suficiencia_equipamiento?->value))
            <x-select-input id="suficiencia_equipamiento" name="suficiencia_equipamiento" class="mt-1 block w-full" x-model="suficienciaEquipo">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\SuficienciaEquipamientoEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($suficienciaActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('suficiencia_equipamiento')" />
        </div>

        <div x-show="suficienciaFalta" x-cloak>
            <x-input-label for="equipamiento_falta" :value="__('¿Qué falta prioritariamente?')" />
            <x-text-input id="equipamiento_falta" name="equipamiento_falta" type="text" class="mt-1 block w-full" :value="old('equipamiento_falta', $infocentro->equipamiento_falta)" />
            <x-input-error class="mt-2" :messages="$errors->get('equipamiento_falta')" />
        </div>
    </div>
</section>
