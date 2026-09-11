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


    public static function buscarPorNombreUsuario(string $nombre, int $idUsuario): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT * FROM carpetas WHERE id_usuario = :usuario AND LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1"
        );
        $stmt->execute(['nombre' => $nombre, 'usuario' => $idUsuario]);
        $carpeta = $stmt->fetch();
        return $carpeta ?: null;
    }

    public static function asegurarCarpetaCategoria(string $categoria, int $idUsuario): int
    {
        $nombres = [
            'Contrato' => 'Contratos',
            'Factura' => 'Facturas',
            'Informe' => 'Informes',
            'Correspondencia' => 'Correspondencia',
        ];

        $nombre = $nombres[$categoria] ?? 'Informes';
        $existente = self::buscarPorNombreUsuario($nombre, $idUsuario);

        if ($existente) {
            return (int) $existente['id_carpeta'];
        }

        return self::crear($nombre, null, $idUsuario);
    }

    public static function buscarPorId(int $id): ?array
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("SELECT * FROM carpetas WHERE id_carpeta = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $carpeta = $stmt->fetch();
        return $carpeta ?: null;
    }

    public static function documentosDeArbol(int $idCarpeta): array
    {
        $pdo = conectarDB();

        // Obtiene la carpeta y todas sus subcarpetas para limpiar también
        // los archivos físicos antes del ON DELETE CASCADE.
        $ids = [$idCarpeta];
        $pendientes = [$idCarpeta];

        while ($pendientes) {
            $actual = array_pop($pendientes);
            $stmt = $pdo->prepare("SELECT id_carpeta FROM carpetas WHERE id_carpeta_padre = :padre");
            $stmt->execute(['padre' => $actual]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $hijo) {
                $hijo = (int) $hijo;
                $ids[] = $hijo;
                $pendientes[] = $hijo;
            }
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id_documento, ruta_almacenamiento FROM documentos WHERE id_carpeta IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public static function eliminar(int $id): void
    {
        $pdo = conectarDB();
        $pdo->beginTransaction();

        try {
            // 1. Obtener TODAS las carpetas que pertenecen al árbol.
            $ids = [$id];
            $pendientes = [$id];

            while (!empty($pendientes)) {
                $actual = (int) array_pop($pendientes);

                $stmt = $pdo->prepare(
                    "SELECT id_carpeta FROM carpetas WHERE id_carpeta_padre = :padre"
                );
                $stmt->execute(['padre' => $actual]);

                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $hijo) {
                    $hijo = (int) $hijo;
                    if (!in_array($hijo, $ids, true)) {
                        $ids[] = $hijo;
                        $pendientes[] = $hijo;
                    }
                }
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // 2. Obtener los documentos del árbol.
            $stmt = $pdo->prepare(
                "SELECT id_documento FROM documentos WHERE id_carpeta IN ($placeholders)"
            );
            $stmt->execute($ids);
            $documentos = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            // 3. IMPORTANTE: eliminar primero las tablas hijas de documentos.
            // Esto hace que funcione incluso si la base de datos existente
            // NO tiene ON DELETE CASCADE configurado.
            if (!empty($documentos)) {
                $docPlaceholders = implode(',', array_fill(0, count($documentos), '?'));

                $tablasHijas = [
                    'metadatos_extraidos',
                    'embeddings',
                    'procesamiento_log'
                ];

                foreach ($tablasHijas as $tabla) {
                    try {
                        $stmt = $pdo->prepare(
                            "DELETE FROM {$tabla} WHERE id_documento IN ($docPlaceholders)"
                        );
                        $stmt->execute($documentos);
                    } catch (PDOException $e) {
                        // Si una instalación antigua no tiene alguna de estas tablas,
                        // continuamos. Las demás tablas sí se eliminan normalmente.
                        if ((int) $e->errorInfo[1] !== 1146) {
                            throw $e;
                        }
                    }
                }

                // 4. Ahora sí eliminamos los documentos.
                $stmt = $pdo->prepare(
                    "DELETE FROM documentos WHERE id_documento IN ($docPlaceholders)"
                );
                $stmt->execute($documentos);
            }

            // 5. Eliminar las carpetas de abajo hacia arriba.
            // No dependemos de ON DELETE CASCADE del FK padre.
            for ($i = count($ids) - 1; $i >= 0; $i--) {
                $stmt = $pdo->prepare(
                    "DELETE FROM carpetas WHERE id_carpeta = :id"
                );
                $stmt->execute(['id' => $ids[$i]]);
            }

            // 6. Verificación real: si la carpeta todavía existe, fallamos.
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM carpetas WHERE id_carpeta IN ($placeholders)"
            );
            $stmt->execute($ids);

            if ((int) $stmt->fetchColumn() > 0) {
                throw new RuntimeException('La carpeta no pudo eliminarse completamente.');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function renombrar(int $id, string $nuevoNombre): void
    {
        $pdo = conectarDB();
        $stmt = $pdo->prepare("UPDATE carpetas SET nombre = :nombre WHERE id_carpeta = :id");
        $stmt->execute(['nombre' => $nuevoNombre, 'id' => $id]);
    }
}
