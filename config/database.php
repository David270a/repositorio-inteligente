<?php
// config/database.php
// Conexión centralizada a MySQL usando PDO con prepared statements.

require_once __DIR__ . '/env.php';

function conectarDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = $_ENV['DB_HOST'];
    $dbname = $_ENV['DB_NAME'];

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die('Error de conexión a la base de datos. Verifique su archivo .env y que MySQL esté iniciado en XAMPP. Detalle: ' . $e->getMessage());
    }
}
