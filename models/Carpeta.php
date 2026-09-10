<?php
// models/Carpeta.php

class Carpeta
{
    public static function crear(string $nombre, ?int $idCarpetaPadre, int $idUsuario): int
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "INSERT INTO carpetas (nombre, id_carpeta_padre, id_usuario) VALUES (:nombre, :padre, :usuario)"
        );
        $stmt->execute([
            'nombre' => $nombre,
            'padre' => $idCarpetaPadre,
            'usuario' => $idUsuario,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function listarPorUsuario(int $idUsuario): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_usuario = :usuario ORDER BY nombre");
        $stmt->execute(['usuario' => $idUsuario]);
        return $stmt->fetchAll();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_carpeta = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $carpeta = $stmt->fetch();
        return $carpeta ?: null;
    }

    public static function eliminar(int $id): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("DELETE FROM carpetas WHERE id_carpeta = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function renombrar(int $id, string $nuevoNombre): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("UPDATE carpetas SET nombre = :nombre WHERE id_carpeta = :id");
        $stmt->execute(['nombre' => $nuevoNombre, 'id' => $id]);
    }
}
