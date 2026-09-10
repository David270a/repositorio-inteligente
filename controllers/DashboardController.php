<?php
// controllers/DashboardController.php

class DashboardController
{
    public function indicadores(): void
    {
        header('Content-Type: application/json');
        $usuario = Sesion::requerirAutenticacion();

        $datos = Documento::indicadores($usuario['id_usuario']);
        echo json_encode($datos);
    }

    public function logs(): void
    {
        header('Content-Type: application/json');
        Sesion::requerirAdministrador();

        echo json_encode(ProcesamientoLog::listarTodos());
    }

    public function mostrarVista(): void
    {
        Sesion::requerirAutenticacion();
        require __DIR__ . '/../views/dashboard.php';
    }
}
