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
        Sesion::requerirAutenticacion();
        Carpeta::eliminar($id);
        echo json_encode(['ok' => true]);
    }

    public function renombrar(int $id): void
    {
        header('Content-Type: application/json');
        Sesion::requerirAutenticacion();

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
