<section aria-labelledby="seccion-e">
    <h3 id="seccion-e" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección E. Modelo de gestión y articulación comunitaria') }}
    </h3>

    <div class="mt-4 grid gap-6">
        <div>
            <x-input-label for="vinculacion_sac" :value="__('E.1. ¿Está vinculado a la Sala de Autogobierno Comunal?')" />
            @php($vinculacionSacActual = old('vinculacion_sac', $infocentro->vinculacion_sac?->value))
            <x-select-input id="vinculacion_sac" name="vinculacion_sac" class="mt-1 block w-full" x-model="vinculacionSac">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\VinculacionEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($vinculacionSacActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('vinculacion_sac')" />
        </div>

        <div x-show="vinculacionSacVinculada" x-cloak class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="vinculacion_sac_desde" :value="__('¿Desde cuándo?')" />
                <x-text-input id="vinculacion_sac_desde" name="vinculacion_sac_desde" type="text" class="mt-1 block w-full" :value="old('vinculacion_sac_desde', $infocentro->vinculacion_sac_desde)" />
                <x-input-error class="mt-2" :messages="$errors->get('vinculacion_sac_desde')" />
            </div>

            <div>
                <x-input-label for="vinculacion_sac_proyectos" :value="__('¿En qué proyectos concretos?')" />
                <x-text-input id="vinculacion_sac_proyectos" name="vinculacion_sac_proyectos" type="text" class="mt-1 block w-full" :value="old('vinculacion_sac_proyectos', $infocentro->vinculacion_sac_proyectos)" />
                <x-input-error class="mt-2" :messages="$errors->get('vinculacion_sac_proyectos')" />
            </div>
        </div>

        <div>
            <x-input-label for="vinculacion_consejos" :value="__('E.2. ¿Está vinculado a los Consejos Comunales del territorio?')" />
            @php($vinculacionConsejosActual = old('vinculacion_consejos', $infocentro->vinculacion_consejos?->value))
            <x-select-input id="vinculacion_consejos" name="vinculacion_consejos" class="mt-1 block w-full" x-model="vinculacionConsejos">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\VinculacionEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($vinculacionConsejosActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('vinculacion_consejos')" />
        </div>

        <div x-show="vinculacionConsejosVinculada" x-cloak class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="consejos_cantidad" :value="__('¿Con cuántos consejos comunales?')" />
                <x-text-input id="consejos_cantidad" name="consejos_cantidad" type="number" min="0" max="99" class="mt-1 block w-32" :value="old('consejos_cantidad', $infocentro->consejos_cantidad)" />
                <x-input-error class="mt-2" :messages="$errors->get('consejos_cantidad')" />
            </div>

            <div>
                <x-input-label for="vinculacion_consejos_proyectos" :value="__('¿En qué proyectos concretos?')" />
                <x-text-input id="vinculacion_consejos_proyectos" name="vinculacion_consejos_proyectos" type="text" class="mt-1 block w-full" :value="old('vinculacion_consejos_proyectos', $infocentro->vinculacion_consejos_proyectos)" />
                <x-input-error class="mt-2" :messages="$errors->get('vinculacion_consejos_proyectos')" />
            </div>
        </div>

        <div>
            <x-si-no
                name="vinculacion_autoridad"
                id="vinculacion_autoridad"
                :pregunta="__('E.3. ¿Está vinculado a la autoridad regional?')"
                model="vinculacionAutoridad"
                :valor="(bool) old('vinculacion_autoridad', $infocentro->vinculacion_autoridad)"
            />
        </div>

        <div x-show="vinculacionAutoridad" x-cloak class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="autoridad_institucion" :value="__('¿Con qué institución?')" />
                <x-text-input id="autoridad_institucion" name="autoridad_institucion" type="text" class="mt-1 block w-full" :value="old('autoridad_institucion', $infocentro->autoridad_institucion)" />
                <x-input-error class="mt-2" :messages="$errors->get('autoridad_institucion')" />
            </div>

            <div>
                <x-input-label for="autoridad_proyecto" :value="__('¿En qué proyecto o convenio concreto?')" />
                <x-text-input id="autoridad_proyecto" name="autoridad_proyecto" type="text" class="mt-1 block w-full" :value="old('autoridad_proyecto', $infocentro->autoridad_proyecto)" />
                <x-input-error class="mt-2" :messages="$errors->get('autoridad_proyecto')" />
            </div>
        </div>

        <div>
            <x-input-label for="consultas_populares" :value="__('E.4. ¿Participa en las Consultas Populares?')" />
            @php($consultasActual = old('consultas_populares', $infocentro->consultas_populares?->value))
            <x-select-input id="consultas_populares" name="consultas_populares" class="mt-1 block w-full" x-model="consultasPopulares">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\ConsultasPopularesEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($consultasActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('consultas_populares')" />
        </div>

        <div x-show="consultasPopularesDetalle" x-cloak>
            <x-input-label for="consultas_populares_detalle" :value="__('¿En cuáles ha participado?')" />
            <x-text-input id="consultas_populares_detalle" name="consultas_populares_detalle" type="text" class="mt-1 block w-full" :value="old('consultas_populares_detalle', $infocentro->consultas_populares_detalle)" />
            <x-input-error class="mt-2" :messages="$errors->get('consultas_populares_detalle')" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="mesa_tecnica" :value="__('E.5. ¿Existe una Mesa Técnica de Ciencia y Tecnología Comunal?')" />
                @php($mesaActual = old('mesa_tecnica', $infocentro->mesa_tecnica?->value))
                <x-select-input id="mesa_tecnica" name="mesa_tecnica" class="mt-1 block w-full">
                    <option value="">{{ __('Seleccione') }}</option>
                    @foreach (\App\Enums\MesaTecnicaEnum::options() as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($mesaActual === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error class="mt-2" :messages="$errors->get('mesa_tecnica')" />
            </div>

            <div>
                <x-input-label for="participacion_comunidad" :value="__('E.6. ¿La comunidad participa en definir las actividades?')" />
                @php($participacionActual = old('participacion_comunidad', $infocentro->participacion_comunidad?->value))
                <x-select-input id="participacion_comunidad" name="participacion_comunidad" class="mt-1 block w-full">
                    <option value="">{{ __('Seleccione') }}</option>
                    @foreach (\App\Enums\ParticipacionComunidadEnum::options() as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($participacionActual === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error class="mt-2" :messages="$errors->get('participacion_comunidad')" />
            </div>
        </div>
    </div>

    <div class="mt-6">
        <x-input-label for="observaciones_gestion" :value="__('E.7. Observaciones sobre modelo de gestión')" />
        <textarea id="observaciones_gestion" name="observaciones_gestion" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('observaciones_gestion', $infocentro->observaciones_gestion) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('observaciones_gestion')" />
    </div>
</section>
