<section aria-labelledby="bloque-firmas">
    <h3 id="bloque-firmas" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Firmas') }}
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ __('Cada persona que participa en la aplicación de la ficha deja su nombre, su firma y la fecha.') }}
    </p>

    <div class="mt-4 space-y-5">
        @foreach (\App\Enums\CargoFirmaEnum::cases() as $cargo)
            @php
                $valores = (array) old("firmas.{$cargo->value}", $infocentro->firmas[$cargo->value] ?? []);
            @endphp

            <fieldset class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <legend class="px-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $cargo->label() }}
                </legend>

                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($cargo->campos() as $campo => $etiqueta)
                        @php
                            $id = "firma_{$cargo->value}_{$campo}";
                        @endphp

                        <div>
                            <x-input-label :for="$id" :value="$etiqueta" />
                            <x-text-input
                                :id="$id"
                                :name="'firmas['.$cargo->value.']['.$campo.']'"
                                :type="$campo === 'fecha' ? 'date' : 'text'"
                                class="mt-1 block w-full"
                                :value="$valores[$campo] ?? null"
                            />
                            <x-input-error
                                class="mt-1"
                                :messages="$errors->get('firmas.'.$cargo->value.'.'.$campo)"
                            />
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
</section>
