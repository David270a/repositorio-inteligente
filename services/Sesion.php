<?php
// services/Sesion.php
// Helpers de sesión y control de acceso por rol.

class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function iniciarSesionUsuario(array $usuario): void
    {
        self::iniciar();
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];
    }

    public static function usuarioActual(): ?array
    {
        self::iniciar();
        if (!isset($_SESSION['id_usuario'])) {
            return null;
        }
        return [
            'id_usuario' => $_SESSION['id_usuario'],
            'nombre' => $_SESSION['nombre'],
            'rol' => $_SESSION['rol'],
        ];
    }

    public static function requerirAutenticacion(): array
    {
        $usuario = self::usuarioActual();
        if ($usuario === null) {
            header('Location: ' . rtrim($_ENV['APP_URL'] ?? '', '/') . '/index.php?ruta=login');
            exit;
        }
        return $usuario;
    }

    public static function requerirAdministrador(): array
    {
        $usuario = self::requerirAutenticacion();
        if ($usuario['rol'] !== 'administrador') {
            http_response_code(403);
            exit('Acceso restringido a administradores.');
        }
        return $usuario;
    }

    public static function cerrarSesion(): void
    {
        self::iniciar();
        $_SESSION = [];
        session_destroy();
    }
}
