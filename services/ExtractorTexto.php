<?php
// services/ExtractorTexto.php
// Extrae el texto plano de un archivo según su tipo (RF-05).

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
        $texto = $pdf->getText();
        return trim($texto);
    }

    private static function extraerDOCX(string $ruta): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($ruta);
        $texto = '';

        foreach ($phpWord->getSections() as $seccion) {
            foreach ($seccion->getElements() as $elemento) {
                if (method_exists($elemento, 'getText')) {
                    $texto .= $elemento->getText() . "\n";
                } elseif (method_exists($elemento, 'getElements')) {
                    foreach ($elemento->getElements() as $sub) {
                        if (method_exists($sub, 'getText')) {
                            $texto .= $sub->getText() . ' ';
                        }
                    }
                    $texto .= "\n";
                }
            }
        }

        return trim($texto);
    }

    private static function extraerTXT(string $ruta): string
    {
        $contenido = file_get_contents($ruta);
        if ($contenido === false) {
            throw new Exception('No fue posible leer el archivo de texto.');
        }
        return trim($contenido);
    }

    /** Divide el texto en fragmentos ("chunks") para generar embeddings (RAG). */
    public static function fragmentar(string $texto, int $tamanoFragmento = 800): array
    {
        $texto = preg_replace('/\s+/', ' ', trim($texto));
        if ($texto === '') {
            return [];
        }
        return array_map('trim', str_split($texto, $tamanoFragmento));
    }
}
