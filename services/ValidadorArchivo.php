<?php
// services/ValidadorArchivo.php
// Valida el tipo real (MIME) y la extensión del archivo cargado, no solo su nombre.

class ValidadorArchivo
{
    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'text/plain' => 'txt',
    ];

    private const TAMANO_MAXIMO = 10 * 1024 * 1024; // 10 MB

    /**
     * @throws Exception si el archivo no es válido
     */
    public static function validar(array $archivo): string
    {
        if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ocurrió un error al recibir el archivo.');
        }

        if ($archivo['size'] > self::TAMANO_MAXIMO) {
            throw new Exception('El archivo supera el tamaño máximo permitido (10 MB).');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new Exception('Formato de archivo no soportado. Solo se aceptan PDF, DOCX y TXT.');
        }

        $extensionReal = self::TIPOS_PERMITIDOS[$mime];
        $extensionNombre = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if ($extensionNombre !== $extensionReal) {
            throw new Exception('La extensión del archivo no coincide con su contenido real.');
        }

        return $extensionReal;
    }
}
