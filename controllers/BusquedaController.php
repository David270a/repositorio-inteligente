<?php
// controllers/BusquedaController.php

class BusquedaController
{
    /** Búsqueda de texto dentro del contenido documental (RF-09) */
    public function buscarTexto(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $termino = trim($_GET['q'] ?? '');
        if ($termino === '') {
            echo json_encode([]);
            return;
        }

        $resultados = Documento::buscarPorContenido($termino, $usuario['id_usuario']);
        echo json_encode($resultados);
    }

    /** Consulta en lenguaje natural sobre el repositorio, vía RAG (RF-10), o en modo chat libre */
    public function consultarLenguajeNatural(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $pregunta = trim($_POST['pregunta'] ?? '');
        $modo = trim($_POST['modo'] ?? 'documentos'); // 'documentos' (RAG) o 'libre' (chat general)
        $idDocumento = !empty($_POST['id_documento']) ? (int) $_POST['id_documento'] : null;

        if ($pregunta === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Debes escribir una pregunta.']);
            return;
        }

        try {
            $buscador = new BuscadorSemantico();

            $resultado = $modo === 'libre'
                ? $buscador->responderLibre($pregunta)
                : $buscador->responderPregunta($pregunta, $usuario['id_usuario'], 5, $idDocumento);

            $pdo = conectarDB();
            $stmt = $pdo->prepare(
                "INSERT INTO consultas_log (id_usuario, pregunta, respuesta) VALUES (:usuario, :pregunta, :respuesta)"
            );
            $stmt->execute([
                'usuario' => $usuario['id_usuario'],
                'pregunta' => $pregunta,
                'respuesta' => $resultado['respuesta'],
            ]);

            echo json_encode($resultado);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'No fue posible generar la respuesta: ' . $e->getMessage()]);
        }
    }
}
