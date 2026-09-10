<?php
// models/Usuario.php

class Usuario
{
    public static function crear(string $nombre, string $correo, string $password, string $rol = 'usuario'): int
    {
        $pdo = conectarDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (:nombre, :correo, :hash, :rol)"
        );
        $stmt->execute([
            'nombre' => $nombre,
            'correo' => $correo,
            'hash' => $hash,
            'rol' => $rol,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function buscarPorCorreo(string $correo): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    public static function correoExiste(string $correo): bool
    {
        return self::buscarPorCorreo($correo) !== null;
    }
}
