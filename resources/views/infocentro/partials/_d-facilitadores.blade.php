<section aria-labelledby="seccion-d" x-data="facilitadores()">
    <h3 id="seccion-d" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección D. Facilitadores') }}
    </h3>

    <div class="mt-4 space-y-4">
        <div>
            <input type="hidden" name="tiene_facilitador_titular" value="0" />
            <label for="tiene_facilitador_titular" class="inline-flex items-center">
                <input id="tiene_facilitador_titular" type="checkbox" name="tiene_facilitador_titular" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    @checked(old('tiene_facilitador_titular', $infocentro->tiene_facilitador_titular)) />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('D.1. ¿Cuenta con facilitador/a titular?') }}</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('tiene_facilitador_titular')" />
        </div>

        <fieldset class="mt-6 space-y-6">
        <div>
            <x-input-label for="cantidad_facilitadores" :value="__('D.2. Número total de facilitadores que atienden el infocentro')" />
            <x-text-input id="cantidad_facilitadores" type="number" min="0" max="50" class="mt-1 block w-32"
                x-model.number="cantidad" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Al cambiar la cantidad se ajustan los bloques del formulario.') }}
            </p>
        </div>

        <div class="space-y-6">
            <p x-show="filas.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('No se registraron facilitadores. Aumente la cantidad para agregarlos.') }}
            </p>

            <template x-for="fila, indice in filas" :key="indice">
                    <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <span>Facilitador </span>
                            <span x-text="indice + 1"></span>
                        </legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="fac_nombre" :value="__('Nombre')" />
                                <x-text-input type="text" class="mt-1 block w-full"
                                    x-model="fila.nombre"
                                    x-bind:name="`facilitadores[nombre][${indice}]`" />
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['nombre.' + indice]" x-text="errores['nombre.' + indice]"></p>
                            </div>

                            <div>
                                <x-input-label for="fac_nivel" :value="__('Nivel académico')" />
                                <x-select-input class="mt-1 block w-full"
                                    x-model="fila.nivel_academico"
                                    x-bind:name="`facilitadores[nivel_academico][${indice}]`">
                                    <option value="">{{ __('Seleccione') }}</option>
                                    <template x-for="(etiqueta, valor) in niveles" :key="valor">
                                        <option :value="valor" x-text="etiqueta"></option>
                                    </template>
                                </x-select-input>
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['nivel_academico.' + indice]" x-text="errores['nivel_academico.' + indice]"></p>
                            </div>

                            <div>
                                <x-input-label for="fac_titulo" :value="__('Título / Carrera')" />
                                <x-text-input type="text" class="mt-1 block w-full"
                                    x-model="fila.titulo_carrera"
                                    x-bind:name="`facilitadores[titulo_carrera][${indice}]`" />
                            </div>

                            <div>
                                <x-input-label for="fac_experiencia" :value="__('Años de experiencia como facilitador')" />
                                <x-text-input type="number" min="0" max="80" class="mt-1 block w-full"
                                    x-model="fila.anios_experiencia"
                                    x-bind:name="`facilitadores[anios_experiencia][${indice}]`" />
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['anios_experiencia.' + indice]" x-text="errores['anios_experiencia.' + indice]"></p>
                            </div>

                            <div>
                                <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Formación en TIC') }}</span>
                                <input type="hidden" x-bind:name="`facilitadores[formacion_tic][${indice}]`" value="0" />
                                <label class="mt-2 inline-flex items-center gap-2">
                                    <input type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm"
                                        x-model="fila.formacion_tic"
                                        x-bind:name="`facilitadores[formacion_tic][${indice}]`" value="1" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Sí') }}</span>
                                </label>
                                <x-text-input type="text" class="mt-2" :placeholder="__('¿Cuál?')"
                                    x-show="fila.formacion_tic"
                                    x-model="fila.formacion_tic_detalle"
                                    x-bind:name="`facilitadores[formacion_tic_detalle][${indice}]`" />
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['formacion_tic_detalle.' + indice]" x-text="errores['formacion_tic_detalle.' + indice]"></p>
                            </div>

                            <div>
                                <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Formación en ciencias') }}</span>
                                <input type="hidden" x-bind:name="`facilitadores[formacion_ciencias][${indice}]`" value="0" />
                                <label class="mt-2 inline-flex items-center gap-2">
                                    <input type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm"
                                        x-model="fila.formacion_ciencias"
                                        x-bind:name="`facilitadores[formacion_ciencias][${indice}]`" value="1" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Sí') }}</span>
                                </label>
                                <x-text-input type="text" class="mt-2" :placeholder="__('¿En qué área?')"
                                    x-show="fila.formacion_ciencias"
                                    x-model="fila.formacion_ciencias_area"
                                    x-bind:name="`facilitadores[formacion_ciencias_area][${indice}]`" />
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['formacion_ciencias_area.' + indice]" x-text="errores['formacion_ciencias_area.' + indice]"></p>
                            </div>

                            <div>
                                <x-input-label for="fac_horas" :value="__('Horas semanales dedicadas al infocentro')" />
                                <x-text-input type="number" min="0" max="168" class="mt-1 block w-full"
                                    x-model="fila.horas_semanales"
                                    x-bind:name="`facilitadores[horas_semanales][${indice}]`" />
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                    x-show="errores['horas_semanales.' + indice]" x-text="errores['horas_semanales.' + indice]"></p>
                            </div>
                        </div>

                        <button type="button" class="text-sm text-red-600 dark:text-red-400 underline" @click="eliminar(indice)">
                            <span x-text="__('Eliminar facilitador')"></span>
                        </button>
                    </fieldset>
                </template>
            </div>
        </fieldset>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="formacion_continua" :value="__('D.4. ¿Los facilitadores reciben formación continua?')" />
            @php($formacionContinuaActual = old('formacion_continua', $infocentro->formacion_continua?->value))
            <x-select-input id="formacion_continua" name="formacion_continua" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\FormacionContinuaEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($formacionContinuaActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('formacion_continua')" />
        </div>

        <div>
            <x-input-label for="formacion_continua_responsable" :value="__('¿Por quién?')" />
            <x-text-input id="formacion_continua_responsable" name="formacion_continua_responsable" type="text" class="mt-1 block w-full" :value="old('formacion_continua_responsable', $infocentro->formacion_continua_responsable)" />
            <x-input-error class="mt-2" :messages="$errors->get('formacion_continua_responsable')" />
        </div>
    </div>

    <div class="mt-6">
        <x-input-label for="observaciones_facilitadores" :value="__('D.5. Observaciones sobre facilitadores')" />
        <textarea id="observaciones_facilitadores" name="observaciones_facilitadores" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('observaciones_facilitadores', $infocentro->observaciones_facilitadores) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('observaciones_facilitadores')" />
    </div>
</section>
