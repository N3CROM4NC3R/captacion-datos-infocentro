<section aria-labelledby="seccion-h" x-data="vinculacionesMincyt()">
    <h3 id="seccion-h" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección H. Vinculación con instituciones del MINCYT y el Estado') }}
    </h3>

    <div class="mt-4 space-y-6">
        <div>
            <input type="hidden" name="vinculacion_mincyt" value="0" />
            <label for="vinculacion_mincyt" class="inline-flex items-center">
                <input id="vinculacion_mincyt" type="checkbox" name="vinculacion_mincyt" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    x-model="vinculado" @checked(old('vinculacion_mincyt', $infocentro->vinculacion_mincyt)) />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('H.1. ¿El infocentro tiene vinculación con instituciones del Ministerio de Ciencia y Tecnología?') }}
                </span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('vinculacion_mincyt')" />
        </div>

        <div x-show="vinculado" x-cloak class="space-y-4">
            <template x-for="(fila, indice) in filas" :key="indice">
                <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                    <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        <span x-text="__('Institución')"></span>
                        <span x-text="indice + 1"></span>
                    </legend>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="vm_institucion" :value="__('Institución')" />
                            <x-text-input id="vm_institucion" type="text" class="mt-1 block w-full"
                                x-model="fila.institucion"
                                x-bind:name="`vinculaciones[institucion][${indice}]`" />
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                x-show="errores['institucion.' + indice]" x-text="errores['institucion.' + indice]"></p>
                        </div>

                        <div>
                            <x-input-label for="vm_proyecto" :value="__('Proyecto concreto')" />
                            <x-text-input id="vm_proyecto" type="text" class="mt-1 block w-full"
                                x-model="fila.proyecto"
                                x-bind:name="`vinculaciones[proyecto][${indice}]`" />
                        </div>

                        <div>
                            <x-input-label for="vm_estado" :value="__('Estado del proyecto')" />
                            <x-select-input id="vm_estado" class="mt-1 block w-full"
                                x-model="fila.estado"
                                x-bind:name="`vinculaciones[estado][${indice}]`">
                                <option value="">{{ __('Seleccione') }}</option>
                                <template x-for="(etiqueta, valor) in estados" :key="valor">
                                    <option :value="valor" x-text="etiqueta"></option>
                                </template>
                            </x-select-input>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                x-show="errores['estado.' + indice]" x-text="errores['estado.' + indice]"></p>
                        </div>

                        <div>
                            <x-input-label for="vm_contacto" :value="__('Última fecha de contacto')" />
                            <x-text-input id="vm_contacto" type="date" class="mt-1 block w-full"
                                x-model="fila.ultimo_contacto"
                                x-bind:name="`vinculaciones[ultimo_contacto][${indice}]`" />
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"
                                x-show="errores['ultimo_contacto.' + indice]" x-text="errores['ultimo_contacto.' + indice]"></p>
                        </div>
                    </div>

                    <button type="button" class="text-sm text-red-600 dark:text-red-400 underline" @click="eliminar(indice)">
                        <span x-text="__('Eliminar institución')"></span>
                    </button>
                </fieldset>
            </template>

            <button type="button" class="text-sm text-indigo-600 dark:text-indigo-400 underline" @click="agregar()">
                <span x-text="__('Agregar institución')"></span>
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <input type="hidden" name="acomp_fundacite" value="0" />
            <label for="acomp_fundacite" class="inline-flex items-center">
                <input id="acomp_fundacite" type="checkbox" name="acomp_fundacite" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    x-model="fundacite" @checked(old('acomp_fundacite', $infocentro->acomp_fundacite)) />
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('H.3. ¿Ha recibido acompañamiento de Fundacite en el último año?') }}
                </span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('acomp_fundacite')" />
        </div>

        <div x-show="fundacite" x-cloak>
            <x-input-label for="acomp_fundacite_detalle" :value="__('¿En qué?')" />
            <x-text-input id="acomp_fundacite_detalle" name="acomp_fundacite_detalle" type="text" class="mt-1 block w-full" :value="old('acomp_fundacite_detalle', $infocentro->acomp_fundacite_detalle)" />
            <x-input-error class="mt-2" :messages="$errors->get('acomp_fundacite_detalle')" />
        </div>
    </div>
</section>
