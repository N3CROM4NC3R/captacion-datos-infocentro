<section aria-labelledby="seccion-j">
    <h3 id="seccion-j" class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ __('Sección J. Documentos anexos') }}
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ __('Adjunta un PDF por cada documento. Todos son opcionales. Al guardar, los archivos que no vuelvas a elegir se conservan.') }}
    </p>

    <div class="mt-4 space-y-5">
        @foreach (\App\Enums\DocumentoAnexoEnum::cases() as $tipo)
            @php
                $documento = $documentos[$tipo->value] ?? null;
                $campoArchivo = "documentos.{$tipo->value}";
                $campoEtiqueta = "documentos_etiqueta.{$tipo->value}";
                $campoEliminar = "documentos_eliminar.{$tipo->value}";
                $etiquetaActual = old($campoEtiqueta, $documento?->etiqueta);
            @endphp

            <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <x-input-label :for="'documento_'.$tipo->value" :value="$tipo->label()" />

                @if ($documento !== null)
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                        <a
                            href="{{ route('documentos.show', $documento) }}"
                            class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            {{ $documento->nombreVisible() }}
                        </a>
                        <span class="text-gray-500 dark:text-gray-400">({{ $documento->tamanoLegible() }})</span>
                        @unless ($documento->existeEnDisco())
                            <span class="text-amber-600 dark:text-amber-400">
                                {{ __('El archivo no está en el servidor; vuelve a adjuntarlo.') }}
                            </span>
                        @endunless
                    </div>

                    <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input
                            type="checkbox"
                            name="documentos_eliminar[{{ $tipo->value }}]"
                            value="1"
                            @checked(old($campoEliminar))
                            class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900"
                        />
                        {{ __('Quitar el documento adjunto') }}
                    </label>
                @endif

                {{-- Un solo nivel de corchetes, a propósito: PHP reparte los
                     atributos del archivo (name, tmp_name…) por el primer
                     nivel, así que con dos el archivo ni siquiera llega a
                     Laravel. Lo cubre el guard de anidamiento de la ficha. --}}
                <input
                    type="file"
                    id="documento_{{ $tipo->value }}"
                    name="documentos[{{ $tipo->value }}]"
                    accept="application/pdf,.pdf"
                    class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300
                           file:me-3 file:rounded-md file:border-0 file:bg-gray-100 dark:file:bg-gray-700
                           file:px-4 file:py-2 file:text-sm file:font-semibold
                           file:text-gray-700 dark:file:text-gray-200"
                />

                @if ($tipo->etiquetaDetalle() !== null)
                    <div class="mt-2">
                        <x-input-label :for="'documento_etiqueta_'.$tipo->value" :value="$tipo->etiquetaDetalle()" />
                        <x-text-input
                            :id="'documento_etiqueta_'.$tipo->value"
                            :name="'documentos_etiqueta['.$tipo->value.']'"
                            type="text"
                            class="mt-1 block w-full"
                            :value="$etiquetaActual"
                        />
                    </div>
                @endif

                <x-input-error class="mt-1" :messages="$errors->get($campoArchivo)" />
                <x-input-error class="mt-1" :messages="$errors->get($campoEtiqueta)" />
                <x-input-error class="mt-1" :messages="$errors->get($campoEliminar)" />
            </div>
        @endforeach
    </div>
</section>
