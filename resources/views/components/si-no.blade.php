@props([
    'name',
    'pregunta',
    'model',
    'id' => null,
    'valor' => false,
    'error' => null,
])

{{--
    Par «Sí / No» de una pregunta de cierre.

    El formulario original las escribe como «( ) Sí ( ) No»: casillas excluyentes
    que dejan claro que la ausencia también es una respuesta. Aquí la casilla de
    «No» es un espejo de la real — `disabled` y sin `name`, de modo que nunca se
    envía — para que no pueda contradecir al dato guardado ni alterar el payload.

    · name    nombre del campo, que solo lleva la casilla de «Sí»
    · pregunta texto de la pregunta
    · model   expresión Alpine que refleja el valor real
    · valor   valor en PHP para el primer renderizado, antes de que Alpine arranque
    · error   clave del bag de errores; por defecto, la del propio campo
--}}
<div>
    <input type="hidden" name="{{ $name }}" value="0" />

    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pregunta }}</p>

    <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-1">
        <label @if ($id) for="{{ $id }}" @endif class="inline-flex items-center">
            <input @if ($id) id="{{ $id }}" @endif type="checkbox" name="{{ $name }}" value="1"
                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                x-model="{{ $model }}"
                @checked($valor) />
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Sí') }}</span>
        </label>

        {{-- El espejo se marca con un atributo derivado, no con `x-model="!…"`.
             `x-model` se compila como destino de una asignación, y `!expresión`
             no lo es: Alpine lanza «Invalid left-hand side in assignment» al
             arrancar y aborta el resto del árbol, dejando sin pintar todo lo
             que venga después en la página. --}}
        <label class="inline-flex items-center">
            <input type="checkbox" disabled
                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-400 shadow-sm opacity-60"
                x-bind:checked="! (Boolean({{ $model }}))" />
            <span class="ms-2 text-sm text-gray-500 dark:text-gray-500">{{ __('No') }}</span>
        </label>
    </div>

    <x-input-error class="mt-2" :messages="$errors->get($error ?? $name)" />
</div>