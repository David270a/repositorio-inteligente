<?php
// controllers/DocumentoController.php

class DocumentoController
{
    public function subir(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        if (empty($_FILES['documento'])) {
            http_response_code(422);
            echo json_encode(['error' => 'No se recibió ningún archivo.']);
            return;
        }

        $idCarpeta = (int) ($_POST['id_carpeta'] ?? 0);
        if ($idCarpeta <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'Debes indicar la carpeta destino.']);
            return;
        }

        try {
            $tipo = ValidadorArchivo::validar($_FILES['documento']);
        } catch (Exception $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        $nombreOriginal = basename($_FILES['documento']['name']);
        $nombreUnico = uniqid('doc_', true) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreOriginal);
        $rutaRelativa = 'uploads/' . $nombreUnico;
        $rutaAbsoluta = __DIR__ . '/../public/' . $rutaRelativa;

        if (!move_uploaded_file($_FILES['documento']['tmp_name'], $rutaAbsoluta)) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo guardar el archivo en el servidor.']);
            return;
        }

        $idDocumento = Documento::crear([
            'id_carpeta' => $idCarpeta,
            'id_usuario' => $usuario['id_usuario'],
            'nombre_archivo' => $nombreOriginal,
            'tipo' => $tipo,
            'ruta_almacenamiento' => $rutaRelativa,
        ]);

        ProcesamientoLog::registrar($idDocumento, 'pendiente');

        // Disparo del procesamiento IA. En esta versión se ejecuta de forma síncrona
        // (adecuado para el alcance de la demo académica); para producción se
        // recomendaría una cola de trabajos (ej. cron o worker) para no bloquear la petición HTTP.
        $documento = Documento::buscarPorId($idDocumento);
        (new OrquestadorIA())->procesar($documento);

        $estadoFinal = ProcesamientoLog::estadoActual($idDocumento);

        echo json_encode([
            'id_documento' => $idDocumento,
            'estado' => $estadoFinal['estado'] ?? 'pendiente',
        ]);
    }

    public function detalle(int $id): void
    {
        header('Content-Type: application/json');
        Sesion::requerirAutenticacion();

        $documento = Documento::buscarPorId($id);
        if (!$documento) {
            http_response_code(404);
            echo json_encode(['error' => 'Documento no encontrado.']);
            return;
        }

        $documento['metadatos'] = MetadatoExtraido::listarPorDocumento($id);
        $documento['estado'] = ProcesamientoLog::estadoActual($id)['estado'] ?? 'pendiente';
        $documento['historial'] = ProcesamientoLog::historialPorDocumento($id);

        echo json_encode($documento);
    }

    public function listarPorCarpeta(int $idCarpeta): void
    {
        header('Content-Type: application/json');
        Sesion::requerirAutenticacion();

        $documentos = Documento::listarPorCarpeta($idCarpeta);
        foreach ($documentos as &$doc) {
            $doc['estado'] = ProcesamientoLog::estadoActual((int) $doc['id_documento'])['estado'] ?? 'pendiente';
        }

        echo json_encode($documentos);
    }

    /** Lista todos los documentos del usuario (sin filtrar por carpeta), para el selector del chat de consulta */
    public function listarTodos(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $documentos = Documento::listarPorUsuario($usuario['id_usuario']);
        $resumen = array_map(fn($doc) => [
            'id_documento' => $doc['id_documento'],
            'nombre_archivo' => $doc['nombre_archivo'],
            'categoria' => $doc['categoria'],
        ], $documentos);

        echo json_encode($resumen);
    }

    public function descargar(int $id): void
    {
        Sesion::requerirAutenticacion();

        $documento = Documento::buscarPorId($id);
        if (!$documento) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }

        $rutaAbsoluta = __DIR__ . '/../public/' . $documento['ruta_almacenamiento'];
        if (!file_exists($rutaAbsoluta)) {
            http_response_code(404);
            exit('El archivo ya no existe en el servidor.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $documento['nombre_archivo'] . '"');
        header('Content-Length: ' . filesize($rutaAbsoluta));
        readfile($rutaAbsoluta);
        exit;
    }

    public function eliminar(int $id): void
    {
        header('Content-Type: application/json');
        Sesion::requerirAutenticacion();

        $documento = Documento::buscarPorId($id);
        if ($documento) {
            $rutaAbsoluta = __DIR__ . '/../public/' . $documento['ruta_almacenamiento'];
            if (file_exists($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
            }
            Documento::eliminar($id); // ON DELETE CASCADE limpia metadatos, embeddings y logs
        }

        echo json_encode(['ok' => true]);
    }
}
