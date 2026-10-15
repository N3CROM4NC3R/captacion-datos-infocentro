<section aria-labelledby="seccion-i" x-data="necesidades()">
    <h3 id="seccion-i" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección I. Necesidades y potencialidades del territorio') }}
    </h3>

    <div class="mt-4">
        <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">
            {{ __('I.1. ¿Qué problemas prioritarios identifica la comunidad?') }}
        </span>

        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach (\App\Enums\ProblemaTerritorioEnum::options() as $valor => $etiqueta)
                <label class="inline-flex items-start gap-2">
                    <input type="checkbox" name="problemas[]" value="{{ $valor }}" x-model="problemas"
                        class="mt-0.5 rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                        @checked(in_array($valor, old('problemas', $infocentro->problemas ?? []), true)) />
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $etiqueta }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('problemas')" />
    </div>

    <div class="mt-4" x-show="problemas.includes('otros')" x-cloak>
        <x-input-label for="problemas_otros" :value="__('Otros (especificar)')" />
        <x-text-input id="problemas_otros" name="problemas_otros" type="text" class="mt-1 block w-full" :value="old('problemas_otros', $infocentro->problemas_otros)" />
        <x-input-error class="mt-2" :messages="$errors->get('problemas_otros')" />
    </div>

    <div class="mt-6">
        <x-input-label for="saberes_recursos" :value="__('I.2. ¿Qué saberes o recursos tiene la comunidad que podrían potenciarse?')" />
        <textarea id="saberes_recursos" name="saberes_recursos" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('saberes_recursos', $infocentro->saberes_recursos) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('saberes_recursos')" />
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="interes_jovenes" :value="__('I.3. ¿Hay jóvenes interesados en formarse en ciencia y tecnología?')" />
            @php($interesJovenesActual = old('interes_jovenes', $infocentro->interes_jovenes?->value))
            <x-select-input id="interes_jovenes" name="interes_jovenes" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\InteresDemandaEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($interesJovenesActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('interes_jovenes')" />
        </div>

        <div>
            <x-input-label for="interes_adultos_mayores" :value="__('I.4. ¿Hay adultos mayores que demanden alfabetización digital?')" />
            @php($interesAdultosActual = old('interes_adultos_mayores', $infocentro->interes_adultos_mayores?->value))
            <x-select-input id="interes_adultos_mayores" name="interes_adultos_mayores" class="mt-1 block w-full">
                <option value="">{{ __('Seleccione') }}</option>
                @foreach (\App\Enums\InteresDemandaEnum::options() as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($interesAdultosActual === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </x-select-input>
            <x-input-error class="mt-2" :messages="$errors->get('interes_adultos_mayores')" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
            <x-si-no
                name="ciencia_ciudadana"
                id="ciencia_ciudadana"
                :pregunta="__('I.5. ¿La comunidad tiene experiencias previas de ciencia ciudadana?')"
                model="cienciaCiudadana"
                :valor="(bool) old('ciencia_ciudadana', $infocentro->ciencia_ciudadana)"
            />
        </div>

        <div x-show="cienciaCiudadana" x-cloak>
            <x-input-label for="ciencia_ciudadana_detalle" :value="__('¿Cuáles?')" />
            <x-text-input id="ciencia_ciudadana_detalle" name="ciencia_ciudadana_detalle" type="text" class="mt-1 block w-full" :value="old('ciencia_ciudadana_detalle', $infocentro->ciencia_ciudadana_detalle)" />
            <x-input-error class="mt-2" :messages="$errors->get('ciencia_ciudadana_detalle')" />
        </div>
    </div>
</section>
