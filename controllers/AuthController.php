<?php
// controllers/AuthController.php

class AuthController
{
    public function mostrarLogin(): void
    {
        require __DIR__ . '/../views/login.php';
    }

    public function mostrarRegistro(): void
    {
        require __DIR__ . '/../views/registro.php';
    }

    public function login(): void
    {
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($correo === '' || $password === '') {
            $this->mostrarLoginConError('Debes ingresar correo y contraseña.');
            return;
        }

        $usuario = Usuario::buscarPorCorreo($correo);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $this->mostrarLoginConError('Correo o contraseña incorrectos.');
            return;
        }

        Sesion::iniciarSesionUsuario($usuario);
        header('Location: ' . rtrim($_ENV['APP_URL'] ?? '', '/') . '/index.php?ruta=repositorio');
        exit;
    }

    public function registrar(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || $correo === '' || strlen($password) < 6) {
            $error = 'Todos los campos son obligatorios y la contraseña debe tener al menos 6 caracteres.';
            require __DIR__ . '/../views/registro.php';
            return;
        }

        if (Usuario::correoExiste($correo)) {
            $error = 'Ya existe una cuenta registrada con ese correo.';
            require __DIR__ . '/../views/registro.php';
            return;
        }

        $idUsuario = Usuario::crear($nombre, $correo, $password, 'usuario');
        $usuario = Usuario::buscarPorId($idUsuario);
        foreach (['Contrato', 'Factura', 'Informe', 'Correspondencia'] as $categoriaBase) {
            Carpeta::asegurarCarpetaCategoria($categoriaBase, (int) $idUsuario);
        }
        Sesion::iniciarSesionUsuario($usuario);

        header('Location: ' . rtrim($_ENV['APP_URL'] ?? '', '/') . '/index.php?ruta=repositorio');
        exit;
    }

    public function logout(): void
    {
        Sesion::cerrarSesion();
        header('Location: ' . rtrim($_ENV['APP_URL'] ?? '', '/') . '/index.php?ruta=login');
        exit;
    }

    private function mostrarLoginConError(string $error): void
    {
        require __DIR__ . '/../views/login.php';
    }
}
