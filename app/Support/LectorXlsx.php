<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lector mínimo de hojas de cálculo .xlsx.
 *
 * Un .xlsx es un ZIP de XML, así que se lee con las extensiones que ya trae PHP
 * (ext-zip y SimpleXML) en lugar de añadir PhpSpreadsheet al proyecto. Solo
 * cubre lo que necesitan los seeders: la primera hoja, celdas de texto y
 * números, resolución de `sharedStrings` y encabezados por nombre de columna.
 */
class LectorXlsx
{
    /** @var array<int, string> */
    private array $compartidas = [];

    private ?SimpleXMLElement $hoja = null;

    /**
     * Read the first sheet of a workbook as rows keyed by column header.
     *
     * @return array<int, array<string, string|null>>
     *
     * @throws RuntimeException
     */
    public function filas(string $ruta): array
    {
        $this->cargar($ruta);

        $filas = [];

        foreach ($this->hoja->sheetData->row as $fila) {
            $celdas = [];

            foreach ($fila->c as $celda) {
                $columna = $this->columna((string) $celda['r']);

                if ($columna !== null) {
                    $celdas[$columna] = $this->valor($celda);
                }
            }

            if ($celdas !== []) {
                $filas[] = $celdas;
            }
        }

        return $this->conEncabezados($filas, $ruta);
    }

    /**
     * Open the workbook and load its shared strings.
     *
     * @throws RuntimeException
     */
    private function cargar(string $ruta): void
    {
        if (! is_file($ruta)) {
            throw new RuntimeException("No se encontró el archivo «{$ruta}».");
        }

        $zip = new ZipArchive;

        if ($zip->open($ruta) !== true) {
            throw new RuntimeException("«{$ruta}» no es un archivo .xlsx legible.");
        }

        try {
            $this->compartidas = $this->leerCompartidas($zip);
            $this->hoja = $this->leerPrimeraHoja($zip, $ruta);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function leerCompartidas(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $compartidas = [];

        foreach (simplexml_load_string($xml) ?? [] as $si) {
            // Un texto puede venir repartido en varios nodos <t>.
            $compartidas[] = (string) $si->t;
        }

        return $compartidas;
    }

    /**
     * @throws RuntimeException
     */
    private function leerPrimeraHoja(ZipArchive $zip, string $ruta): SimpleXMLElement
    {
        // El orden de las hojas vive en el libro; en la práctica el seed solo
        // usa el primer worksheet, que es lo que se documenta como primera hoja.
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($xml === false) {
            throw new RuntimeException("«{$ruta}» no contiene ninguna hoja legible.");
        }

        $hoja = simplexml_load_string($xml);

        if ($hoja === false) {
            throw new RuntimeException("No se pudo interpretar la hoja de «{$ruta}».");
        }

        return $hoja;
    }

    /**
     * Resolve a cell to its scalar value, following its declared type.
     */
    private function valor(SimpleXMLElement $celda): ?string
    {
        // Las celdas en línea llevan el texto dentro, sin <v>.
        if (isset($celda->is)) {
            return trim((string) $celda->is->t);
        }

        if (! isset($celda->v)) {
            return null;
        }

        $bruto = (string) $celda->v;

        // `s` significa índice dentro de la tabla de textos compartidos.
        return ((string) $celda['t']) === 's'
            ? ($this->compartidas[(int) $bruto] ?? null)
            : $bruto;
    }

    /**
     * Turn "A", "B", "AB" into a zero-based column index, or null if malformed.
     */
    private function columna(string $referencia): ?int
    {
        if (! preg_match('/^([A-Z]+)/', $referencia, $letras)) {
            return null;
        }

        $indice = 0;

        foreach (str_split($letras[1]) as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }

        return $indice - 1;
    }

    /**
     * Replace positional columns with the names declared in the first row.
     *
     * @param  array<int, array<int, string|null>>  $filas
     * @return array<int, array<string, string|null>>
     *
     * @throws RuntimeException
     */
    private function conEncabezados(array $filas, string $ruta): array
    {
        if ($filas === []) {
            throw new RuntimeException("«{$ruta}» está vacío.");
        }

        $encabezados = [];

        foreach ($filas[0] as $indice => $titulo) {
            $titulo = trim((string) $titulo);

            if ($titulo === '') {
                throw new RuntimeException("«{$ruta}» tiene una columna sin nombre en la posición ".($indice + 1).'.');
            }

            $encabezados[$indice] = $titulo;
        }

        array_shift($filas);

        $conNombres = [];

        foreach ($filas as $fila) {
            $registro = [];

            foreach ($encabezados as $indice => $titulo) {
                $registro[$titulo] = $fila[$indice] ?? null;
            }

            $conNombres[] = $registro;
        }

        return $conNombres;
    }
}
