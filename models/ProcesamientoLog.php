<?php
// models/ProcesamientoLog.php

class ProcesamientoLog
{
    public static function registrar(int $idDocumento, string $estado, ?string $mensajeError = null): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "INSERT INTO procesamiento_log (id_documento, estado, mensaje_error) VALUES (:doc, :estado, :error)"
        );
        $stmt->execute(['doc' => $idDocumento, 'estado' => $estado, 'error' => $mensajeError]);
    }

    public static function estadoActual(int $idDocumento): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT * FROM procesamiento_log WHERE id_documento = :doc ORDER BY id_log DESC LIMIT 1"
        );
        $stmt->execute(['doc' => $idDocumento]);
        $log = $stmt->fetch();
        return $log ?: null;
    }

    public static function historialPorDocumento(int $idDocumento): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT * FROM procesamiento_log WHERE id_documento = :doc ORDER BY id_log ASC"
        );
        $stmt->execute(['doc' => $idDocumento]);
        return $stmt->fetchAll();
    }

    /** Listado global de errores/estados, solo para administrador (RF-12) */
    public static function listarTodos(int $limite = 100): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT pl.*, d.nombre_archivo, u.nombre AS usuario
             FROM procesamiento_log pl
             INNER JOIN documentos d ON d.id_documento = pl.id_documento
             INNER JOIN usuarios u ON u.id_usuario = d.id_usuario
             INNER JOIN (
                 SELECT id_documento, MAX(id_log) AS ultimo_log
                 FROM procesamiento_log
                 GROUP BY id_documento
             ) ult ON ult.id_documento = pl.id_documento AND ult.ultimo_log = pl.id_log
             ORDER BY pl.fecha_evento DESC
             LIMIT :limite"
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
