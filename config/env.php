<?php
// config/env.php
// Carga las variables de entorno desde el archivo .env usando vlucas/phpdotenv.

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad(); // no lanza error si .env no existe (útil en algunos entornos de prueba)

// Valores por defecto si alguna variable no está definida en .env
$_ENV['DB_HOST']    = $_ENV['DB_HOST']    ?? 'localhost';
$_ENV['DB_NAME']    = $_ENV['DB_NAME']    ?? 'repositorio_inteligente';
$_ENV['DB_USER']    = $_ENV['DB_USER']    ?? 'root';
$_ENV['DB_PASS']    = $_ENV['DB_PASS']    ?? '';
$_ENV['IA_PROVEEDOR']= $_ENV['IA_PROVEEDOR'] ?? 'anthropic';
$_ENV['IA_API_KEY'] = $_ENV['IA_API_KEY'] ?? '';
$_ENV['IA_MODEL']   = $_ENV['IA_MODEL']   ?? 'claude-sonnet-4-6';
$_ENV['IA_ENDPOINT_ANTHROPIC'] = $_ENV['IA_ENDPOINT_ANTHROPIC'] ?? '';
$_ENV['IA_ENDPOINT_OPENAI']    = $_ENV['IA_ENDPOINT_OPENAI']    ?? '';
$_ENV['EMBEDDINGS_API_KEY'] = $_ENV['EMBEDDINGS_API_KEY'] ?? '';
$_ENV['EMBEDDINGS_MODEL']   = $_ENV['EMBEDDINGS_MODEL']   ?? 'text-embedding-3-small';
$_ENV['APP_DEBUG']  = $_ENV['APP_DEBUG']  ?? 'true';
