<?php
// services/ExtractorTexto.php
// Extrae texto de PDF, DOCX y TXT.

class ExtractorTexto
{
    public static function extraer(string $rutaAbsoluta, string $tipo): string
    {
        return match ($tipo) {
            'pdf' => self::extraerPDF($rutaAbsoluta),
            'docx' => self::extraerDOCX($rutaAbsoluta),
            'txt' => self::extraerTXT($rutaAbsoluta),
            default => throw new Exception("Tipo de archivo no soportado para extracción: {$tipo}"),
        };
    }

    private static function extraerPDF(string $ruta): string
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($ruta);
        return trim($pdf->getText());
    }

    private static function extraerDOCX(string $ruta): string
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('El servidor no tiene habilitada la extensión ZIP de PHP.');
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new Exception('No se pudo abrir el archivo DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new Exception('El DOCX no contiene word/document.xml.');
        }

        // Leemos el XML de Word de forma estructurada para no perder
        // espacios ni separar incorrectamente las palabras de cada párrafo.
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();

        if (!$dom->loadXML($xml)) {
            libxml_clear_errors();
            throw new Exception('El contenido interno del DOCX no es un XML válido.');
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $parrafos = $xpath->query('//w:p');
        $lineas = [];

        foreach ($parrafos as $parrafo) {
            $partes = [];

            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $parrafo) as $nodo) {
                if ($nodo->localName === 't') {
                    $partes[] = $nodo->textContent;
                } elseif ($nodo->localName === 'tab') {
                    $partes[] = "\t";
                } else {
                    $partes[] = "\n";
                }
            }

            $linea = trim(implode('', $partes));
            if ($linea !== '') {
                $lineas[] = $linea;
            }
        }

        // Si el documento no tiene párrafos detectables, usamos una
        // extracción de respaldo del XML.
        if (!$lineas) {
            $texto = strip_tags($xml);
            $texto = html_entity_decode($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
            return trim($texto);
        }

        return trim(implode("\n", $lineas));
    }

    private static function extraerTXT(string $ruta): string
    {
        $contenido = file_get_contents($ruta);
        if ($contenido === false) {
            throw new Exception('No fue posible leer el archivo de texto.');
        }
        return trim($contenido);
    }

    public static function fragmentar(string $texto, int $tamanoFragmento = 800): array
    {
        $texto = preg_replace('/\s+/', ' ', trim($texto));
        if ($texto === '') {
            return [];
        }
        return array_map('trim', str_split($texto, $tamanoFragmento));
    }
}
