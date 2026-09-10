<?php
// models/Documento.php

class Documento
{
    public static function crear(array $datos): int
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "INSERT INTO documentos (id_carpeta, id_usuario, nombre_archivo, tipo, ruta_almacenamiento, fecha_carga)
             VALUES (:carpeta, :usuario, :nombre, :tipo, :ruta, NOW())"
        );
        $stmt->execute([
            'carpeta' => $datos['id_carpeta'],
            'usuario' => $datos['id_usuario'],
            'nombre' => $datos['nombre_archivo'],
            'tipo' => $datos['tipo'],
            'ruta' => $datos['ruta_almacenamiento'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id_documento = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $doc = $stmt->fetch();
        return $doc ?: null;
    }

    public static function listarPorCarpeta(int $idCarpeta): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id_carpeta = :carpeta ORDER BY fecha_carga DESC");
        $stmt->execute(['carpeta' => $idCarpeta]);
        return $stmt->fetchAll();
    }

    public static function listarPorUsuario(int $idUsuario): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id_usuario = :usuario ORDER BY fecha_carga DESC");
        $stmt->execute(['usuario' => $idUsuario]);
        return $stmt->fetchAll();
    }

    public static function actualizarResultadosIA(int $id, string $textoExtraido, string $categoria, string $resumen): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "UPDATE documentos SET texto_extraido = :texto, categoria = :categoria, resumen = :resumen WHERE id_documento = :id"
        );
        $stmt->execute([
            'texto' => $textoExtraido,
            'categoria' => $categoria,
            'resumen' => $resumen,
            'id' => $id,
        ]);
    }

    public static function eliminar(int $id): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("DELETE FROM documentos WHERE id_documento = :id");
        $stmt->execute(['id' => $id]);
    }

    /** Búsqueda de texto completo dentro del contenido documental (RF-09) */
    public static function buscarPorContenido(string $termino, int $idUsuario): array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT *, MATCH(texto_extraido) AGAINST(:termino IN NATURAL LANGUAGE MODE) AS relevancia
             FROM documentos
             WHERE id_usuario = :usuario AND MATCH(texto_extraido) AGAINST(:termino2 IN NATURAL LANGUAGE MODE)
             ORDER BY relevancia DESC"
        );
        $stmt->execute(['termino' => $termino, 'termino2' => $termino, 'usuario' => $idUsuario]);
        return $stmt->fetchAll();
    }

    /** Indicadores agregados para el dashboard (RF-11) */
    public static function indicadores(int $idUsuario): array
    {
        $pdo = conectarDB();

        $total = $pdo->prepare("SELECT COUNT(*) AS total FROM documentos WHERE id_usuario = :usuario");
        $total->execute(['usuario' => $idUsuario]);

        $porCategoria = $pdo->prepare(
            "SELECT COALESCE(categoria, 'Sin clasificar') AS categoria, COUNT(*) AS cantidad
             FROM documentos WHERE id_usuario = :usuario GROUP BY categoria"
        );
        $porCategoria->execute(['usuario' => $idUsuario]);

        $porEstado = $pdo->prepare(
            "SELECT pl.estado, COUNT(DISTINCT pl.id_documento) AS cantidad
             FROM procesamiento_log pl
             INNER JOIN documentos d ON d.id_documento = pl.id_documento
             WHERE d.id_usuario = :usuario
             AND pl.id_log = (SELECT MAX(id_log) FROM procesamiento_log WHERE id_documento = pl.id_documento)
             GROUP BY pl.estado"
        );
        $porEstado->execute(['usuario' => $idUsuario]);

        return [
            'total' => (int) $total->fetch()['total'],
            'por_categoria' => $porCategoria->fetchAll(),
            'por_estado' => $porEstado->fetchAll(),
        ];
    }
}
