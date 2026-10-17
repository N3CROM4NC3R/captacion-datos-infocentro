<?php

use App\Enums\DocumentoAnexoEnum;
use App\Models\DocumentoAnexo;
use App\Models\Estado;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Guardar la ficha tal y como la envía el navegador: un POST con
 * `_method=PUT` y archivos adjuntos, no una llamada PUT con los archivos ya
 * inyectados. Es la diferencia que decide si la sección J funciona.
 *
 * @param  array<string, mixed>  $datos
 */
function guardarFichaComoNavegador($user, array $datos = []): TestResponse
{
    $estado = Estado::factory()->create();

    return test()->actingAs($user)->post('/infocentro/diagnostico', [
        '_method' => 'PUT',
        'nombre' => 'Infocentro',
        'estado_id' => $estado->id,
        'activo' => '1',
        ...$datos,
    ]);
}

/**
 * Un PDF falso con contenido reconocible.
 */
function pdfFalso(string $nombre = 'espacio.pdf', int $kilos = 50): UploadedFile
{
    return UploadedFile::fake()->create($nombre, $kilos, 'application/pdf');
}

beforeEach(function () {
    Storage::fake('local');
});

test('la ficha muestra la sección J y el bloque de firmas', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee(__('Sección J. Documentos anexos'))
        ->assertSee(__('Firmas'));
});

test('el formulario se envía como multipart', function () {
    $user = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')->getContent();

    // Sin esto el navegador manda application/x-www-form-urlencoded y los
    // archivos se pierden sin un solo error.
    expect(str_contains($html, 'enctype="multipart/form-data"'))->toBeTrue(
        'El formulario de la ficha debe declarar enctype="multipart/form-data".',
    );
});

test('la sección J ofrece un campo de archivo por cada documento del PDF', function () {
    $user = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')->getContent();

    expect(DocumentoAnexoEnum::cases())->toHaveCount(6);

    foreach (DocumentoAnexoEnum::cases() as $tipo) {
        expect(str_contains($html, 'name="documentos['.$tipo->value.']"'))->toBeTrue(
            "Falta el campo de archivo para «{$tipo->label()}».",
        );
    }
});

test('ningún campo de archivo se anida más de un nivel', function () {
    $user = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')->getContent();

    preg_match_all('/type="file"[^>]*name="([^"]+)"/', $html, $coincidencias);

    $nombres = $coincidencias[1];

    expect($nombres)->not->toBeEmpty();

    // PHP reparte los atributos del archivo (name, tmp_name, error, size) por
    // el PRIMER nivel del nombre del campo. Con dos niveles el archivo no
    // llega a Laravel: desaparece de $_FILES sin error ni aviso, y la
    // validación lo da por buena porque sencillamente no lo ve.
    //
    // El cliente de pruebas de Laravel construye el FileBag directamente y no
    // pasa por el parser de $_FILES, así que esta restricción no se puede
    // comprobar subiendo un archivo: se comprueba sobre el HTML.
    foreach ($nombres as $nombre) {
        expect(substr_count($nombre, '['))->toBeLessThanOrEqual(
            1,
            "El campo de archivo «{$nombre}» se anida más de un nivel: PHP no lo entregará a Laravel.",
        );
    }
});

test('un PDF adjunto por POST multipart con _method=PUT llega al disco', function () {
    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso()],
    ])->assertSessionHasNoErrors();

    $documento = DocumentoAnexo::sole();

    expect($documento->infocentro_id)->toBe($infocentro->id)
        ->and($documento->tipo)->toBe(DocumentoAnexoEnum::FotosEspacio)
        ->and($documento->nombre_original)->toBe('espacio.pdf')
        ->and(Storage::disk('local')->exists($documento->ruta))->toBeTrue(
            'El archivo no llegó al disco: el POST multipart con _method=PUT no lo entregó.',
        );
});

test('los seis documentos se pueden adjuntar en el mismo guardado', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [
            DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf'),
            DocumentoAnexoEnum::FotosEquipamiento->value => pdfFalso('equipo.pdf'),
            DocumentoAnexoEnum::ConvenioSac->value => pdfFalso('convenio.pdf'),
            DocumentoAnexoEnum::PlanActividades->value => pdfFalso('plan.pdf'),
            DocumentoAnexoEnum::ListadoParticipantes->value => pdfFalso('participantes.pdf'),
            DocumentoAnexoEnum::Otros->value => pdfFalso('extra.pdf'),
        ],
        'documentos_etiqueta' => [DocumentoAnexoEnum::Otros->value => 'Acta de la asamblea'],
    ])->assertSessionHasNoErrors();

    expect(DocumentoAnexo::count())->toBe(6)
        ->and(DocumentoAnexo::where('tipo', DocumentoAnexoEnum::Otros->value)->sole()->etiqueta)
        ->toBe('Acta de la asamblea');
});

test('el archivo se guarda en un disco privado, no bajo public', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso()],
    ])->assertSessionHasNoErrors();

    $ruta = DocumentoAnexo::sole()->ruta;

    expect($ruta)->toStartWith('diagnostico/')
        ->and(file_exists(public_path($ruta)))->toBeFalse(
            'Los documentos anexos no pueden quedar dentro de public/.',
        );
});

test('se rechazan los archivos que no son PDF', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [
            DocumentoAnexoEnum::FotosEspacio->value => UploadedFile::fake()
                ->create('ataque.php', 10, 'application/x-php'),
        ],
    ])->assertSessionHasErrors('documentos.'.DocumentoAnexoEnum::FotosEspacio->value);

    expect(DocumentoAnexo::count())->toBe(0);
});

test('se rechazan los archivos que superan el tamaño máximo', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('grande.pdf', 10_241)],
    ])->assertSessionHasErrors('documentos.'.DocumentoAnexoEnum::FotosEspacio->value);

    expect(DocumentoAnexo::count())->toBe(0);
});

test('«Otros» exige indicar qué documento es', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::Otros->value => pdfFalso('extra.pdf')],
    ])->assertSessionHasErrors('documentos_etiqueta.'.DocumentoAnexoEnum::Otros->value);

    expect(DocumentoAnexo::count())->toBe(0);
});

test('no se puede eliminar y subir un documento del mismo tipo a la vez', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::PlanActividades->value => pdfFalso('plan.pdf')],
        'documentos_eliminar' => [DocumentoAnexoEnum::PlanActividades->value => '1'],
    ])->assertSessionHasErrors('documentos_eliminar.'.DocumentoAnexoEnum::PlanActividades->value);
});

test('un tipo de documento desconocido se rechaza', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos_etiqueta' => ['inventado' => 'lo que sea'],
    ])->assertSessionHasErrors('documentos.inventado');
});

test('un input de archivo vacío no cuenta como documento', function () {
    $user = InfocentroUserFactory::new()->create();

    // Lo que llega de un `<input type="file">` sin elegir nada.
    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => ''],
    ])->assertSessionHasNoErrors();

    expect(DocumentoAnexo::count())->toBe(0);
});

test('una ficha guardada no deja ni corregir campos ni tocar los documentos', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'telefono' => '04121234567',
        'documentos' => [
            DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf'),
            DocumentoAnexoEnum::ConvenioSac->value => pdfFalso('convenio.pdf'),
        ],
    ])->assertSessionHasNoErrors();

    $primera = DocumentoAnexo::orderBy('tipo')->get()->keyBy(fn ($d) => $d->tipo->value);
    $telefono = $user->infocentro->refresh()->telefono;

    // Antes esto comprobaba que un segundo guardado conservaba los archivos ya
    // subidos, porque un input de archivo llega vacío cuando no se toca y
    // borrarlos todos tiraría lo ya subido. Con la ficha cerrada ese segundo
    // guardado no entra: ni toca el campo ni los documentos.
    guardarFichaComoNavegador($user, [
        'telefono' => '00000000',
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('otro.pdf')],
    ])->assertSessionHas('aviso');

    expect(DocumentoAnexo::count())->toBe(2)
        ->and($user->infocentro->documentosAnexos()->where('tipo', 'fotos_espacio')->sole()->nombre_original)
        ->toBe($primera['fotos_espacio']->nombre_original)
        ->and($user->infocentro->documentosAnexos()->where('tipo', 'convenio_sac')->sole()->created_at)
        ->toEqual($primera['convenio_sac']->created_at)
        ->and($user->infocentro->refresh()->telefono)->toBe($telefono);
});

test('un documento ya subido no se puede sustituir', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('v1.pdf')],
    ])->assertSessionHasNoErrors();

    $ruta = DocumentoAnexo::sole()->ruta;

    // Antes este test comprobaba que resubir el mismo tipo lo sustituía. Con la
    // ficha cerrada el segundo guardado no entra, así que el documento que se
    // queda es el primero.
    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('v2.pdf')],
    ])->assertSessionHas('aviso');

    expect(DocumentoAnexo::count())->toBe(1)
        ->and(DocumentoAnexo::sole()->nombre_original)->toBe('v1.pdf')
        ->and(Storage::disk('local')->exists($ruta))->toBeTrue();
});

test('un documento guardado ya no se puede quitar desde la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf')],
    ])->assertSessionHasNoErrors();

    $ruta = DocumentoAnexo::sole()->ruta;

    expect(Storage::disk('local')->exists($ruta))->toBeTrue();

    // Antes esto comprobaba que marcar el documento para eliminar borraba su
    // archivo. Con la ficha cerrada el guardado no entra, así que el documento
    // sigue ahí con su archivo. Quitarlo ahora es cosa del administrador, que
    // vacía la ficha entera.
    guardarFichaComoNavegador($user, [
        'documentos_eliminar' => [DocumentoAnexoEnum::FotosEspacio->value => '1'],
    ])->assertSessionHas('aviso');

    expect(DocumentoAnexo::count())->toBe(1)
        ->and(Storage::disk('local')->exists($ruta))->toBeTrue();
});

test('el infocentro descarga su propio documento', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf')],
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->get(route('documentos.show', DocumentoAnexo::sole()))
        ->assertOk()
        ->assertDownload('espacio.pdf');
});

test('otro infocentro no puede descargar el documento', function () {
    $dueno = InfocentroUserFactory::new()->create();
    $otro = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($dueno, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('secreto.pdf')],
    ])->assertSessionHasNoErrors();

    $this->actingAs($otro)
        ->get(route('documentos.show', DocumentoAnexo::sole()))
        ->assertForbidden();
});

test('el administrador sí puede descargar los documentos anexos', function () {
    $dueno = InfocentroUserFactory::new()->create();
    $admin = AdminFactory::new()->create();

    guardarFichaComoNavegador($dueno, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf')],
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get(route('documentos.show', DocumentoAnexo::sole()))
        ->assertOk()
        ->assertDownload('espacio.pdf');
});

test('sin iniciar sesión no hay descarga', function () {
    $this->get('/documentos/1')->assertRedirect(route('login'));
});

test('un documento cuyo archivo ya no está en el disco avisa en la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarFichaComoNavegador($user, [
        'documentos' => [DocumentoAnexoEnum::FotosEspacio->value => pdfFalso('espacio.pdf')],
    ])->assertSessionHasNoErrors();

    Storage::disk('local')->delete(DocumentoAnexo::sole()->ruta);

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee(__('El archivo no está en el servidor; vuelve a adjuntarlo.'));

    $this->actingAs($user)
        ->get(route('documentos.show', DocumentoAnexo::sole()))
        ->assertNotFound();
});
