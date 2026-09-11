<?php
// controllers/CarpetaController.php

class CarpetaController
{
    public function crear(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            http_response_code(422);
            echo json_encode(['error' => 'El nombre de la carpeta es obligatorio.']);
            return;
        }

        $padre = !empty($_POST['id_carpeta_padre']) ? (int) $_POST['id_carpeta_padre'] : null;
        $id = Carpeta::crear($nombre, $padre, $usuario['id_usuario']);

        echo json_encode(['id_carpeta' => $id, 'nombre' => $nombre]);
    }

    public function eliminar(int $id): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();
        $carpeta = Carpeta::buscarPorId($id);

        if (!$carpeta) {
            http_response_code(404);
            echo json_encode(['error' => 'La carpeta no existe.']);
            return;
        }

        if ((int) $carpeta['id_usuario'] !== (int) $usuario['id_usuario']) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para eliminar esta carpeta.']);
            return;
        }

        try {
            // Guardamos las rutas antes de eliminar los registros.
            $documentos = Carpeta::documentosDeArbol($id);

            Carpeta::eliminar($id);

            // El borrado de la BD ya fue confirmado. Ahora limpiamos los archivos físicos.
            foreach ($documentos as $documento) {
            $ruta = str_replace(['\\', '..'], ['/', ''], (string) $documento['ruta_almacenamiento']);
            $rutaAbsoluta = realpath(__DIR__ . '/../public/' . ltrim($ruta, '/'));
            $directorioUploads = realpath(__DIR__ . '/../public/uploads');

            if ($rutaAbsoluta && $directorioUploads && strpos($rutaAbsoluta, $directorioUploads . DIRECTORY_SEPARATOR) === 0 && is_file($rutaAbsoluta)) {
                    @unlink($rutaAbsoluta);
                }
            }

            echo json_encode(['ok' => true, 'id_carpeta' => $id]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'No se pudo eliminar la carpeta. Verifica las restricciones de la base de datos.',
                'detalle' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? $e->getMessage() : null
            ]);
        }
    }

    public function renombrar(int $id): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $carpeta = Carpeta::buscarPorId($id);
        if (!$carpeta) {
            http_response_code(404);
            echo json_encode(['error' => 'La carpeta no existe.']);
            return;
        }
        if ((int) $carpeta['id_usuario'] !== (int) $usuario['id_usuario']) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes permiso para renombrar esta carpeta.']);
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            http_response_code(422);
            echo json_encode(['error' => 'El nombre no puede estar vacío.']);
            return;
        }

        Carpeta::renombrar($id, $nombre);
        echo json_encode(['ok' => true]);
    }
}
