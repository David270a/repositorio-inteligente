<?php
// services/ValidadorArchivo.php
// Valida PDF, DOCX y TXT. DOCX se valida por su estructura ZIP/OOXML
// porque el MIME de un .docx puede variar según el servidor.

class ValidadorArchivo
{
    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'application/x-pdf' => 'pdf',
        'text/plain' => 'txt',
    ];

    private const TAMANO_MAXIMO = 10 * 1024 * 1024; // 10 MB

    public static function validar(array $archivo): string
    {
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ocurrió un error al recibir el archivo.');
        }

        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            throw new Exception('El archivo recibido no es válido.');
        }

        if ((int)$archivo['size'] <= 0) {
            throw new Exception('El archivo está vacío.');
        }

        if ((int)$archivo['size'] > self::TAMANO_MAXIMO) {
            throw new Exception('El archivo supera el tamaño máximo permitido (10 MB).');
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        // DOCX: algunos servidores lo identifican como application/zip.
        if ($extension === 'docx') {
            if (!class_exists('ZipArchive')) {
                throw new Exception('El servidor no tiene habilitada la extensión ZIP de PHP, necesaria para archivos DOCX.');
            }

            $zip = new ZipArchive();
            $abierto = $zip->open($archivo['tmp_name']);
            if ($abierto !== true) {
                throw new Exception('El archivo DOCX no es un documento Word válido.');
            }

            $esDocx = $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false;
            $zip->close();

            if (!$esDocx) {
                throw new Exception('El archivo tiene extensión DOCX, pero su estructura no es válida.');
            }

            return 'docx';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === false || !isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new Exception('Formato de archivo no soportado. Solo se aceptan PDF, DOCX y TXT.');
        }

        $tipo = self::TIPOS_PERMITIDOS[$mime];

        if ($extension !== $tipo) {
            throw new Exception('La extensión del archivo no coincide con su contenido real.');
        }

        return $tipo;
    }
}
