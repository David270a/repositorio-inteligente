<?php
// models/MetadatoExtraido.php

class MetadatoExtraido
{
    public static function crear(int $idDocumento, string $campo, string $valor): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "INSERT INTO metadatos_extraidos (id_documento, campo, valor) VALUES (:doc, :campo, :valor)"
        );
        $stmt->execute(['doc' => $idDocumento, 'campo' => $campo, 'valor' => $valor]);
    }

    public static function eliminarPorDocumento(int $idDocumento): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("DELETE FROM metadatos_extraidos WHERE id_documento = :doc");
        $stmt->execute(['doc' => $idDocumento]);
    }

    public static function listarPorDocumento(int $idDocumento): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT campo, valor FROM metadatos_extraidos WHERE id_documento = :doc");
        $stmt->execute(['doc' => $idDocumento]);
        return $stmt->fetchAll();
    }
}
