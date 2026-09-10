<?php
// models/Embedding.php

class Embedding
{
    public static function crear(int $idDocumento, string $fragmento, array $vector): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "INSERT INTO embeddings (id_documento, fragmento_texto, vector) VALUES (:doc, :fragmento, :vector)"
        );
        $stmt->execute([
            'doc' => $idDocumento,
            'fragmento' => $fragmento,
            'vector' => json_encode($vector),
        ]);
    }

    public static function eliminarPorDocumento(int $idDocumento): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("DELETE FROM embeddings WHERE id_documento = :doc");
        $stmt->execute(['doc' => $idDocumento]);
    }

    /**
     * Trae los fragmentos + vectores de los documentos de un usuario (para RAG).
     * Si $idDocumento se indica, restringe la búsqueda a un único documento
     * (usado cuando el usuario elige preguntar sobre un documento específico).
     */
    public static function obtenerPorUsuario(int $idUsuario, ?int $idDocumento = null): array
    {
        $pdo = conectarDB();
        $sql = "SELECT e.id_embedding, e.id_documento, e.fragmento_texto, e.vector, d.nombre_archivo
                FROM embeddings e
                INNER JOIN documentos d ON d.id_documento = e.id_documento
                WHERE d.id_usuario = :usuario";
        $parametros = ['usuario' => $idUsuario];

        if ($idDocumento !== null) {
            $sql .= " AND e.id_documento = :documento";
            $parametros['documento'] = $idDocumento;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }
}
