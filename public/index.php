<?php
// public/index.php
// Front controller: todas las peticiones pasan por aquí (ver public/.htaccess).

require_once __DIR__ . '/../config/database.php';

// Autoload sencillo de clases del proyecto (sin necesidad de Composer para esto)
spl_autoload_register(function ($clase) {
    $rutas = [
        __DIR__ . "/../models/{$clase}.php",
        __DIR__ . "/../services/{$clase}.php",
        __DIR__ . "/../controllers/{$clase}.php",
    ];
    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            require_once $ruta;
            return;
        }
    }
});

Sesion::iniciar();

$ruta = $_GET['ruta'] ?? 'repositorio';
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch (true) {
        // ---------- Autenticación ----------
        case $ruta === 'login' && $metodo === 'GET':
            (new AuthController())->mostrarLogin();
            break;
        case $ruta === 'login' && $metodo === 'POST':
            (new AuthController())->login();
            break;
        case $ruta === 'registro' && $metodo === 'GET':
            (new AuthController())->mostrarRegistro();
            break;
        case $ruta === 'registro' && $metodo === 'POST':
            (new AuthController())->registrar();
            break;
        case $ruta === 'logout':
            (new AuthController())->logout();
            break;

        // ---------- Vistas principales ----------
        case $ruta === 'repositorio':
            Sesion::requerirAutenticacion();
            require __DIR__ . '/../views/repositorio.php';
            break;
        case $ruta === 'consulta':
            Sesion::requerirAutenticacion();
            require __DIR__ . '/../views/consulta.php';
            break;
        case $ruta === 'dashboard':
            (new DashboardController())->mostrarVista();
            break;

        // ---------- API: Carpetas ----------
        case $ruta === 'api/carpetas' && $metodo === 'POST':
            (new CarpetaController())->crear();
            break;
        case preg_match('#^api/carpetas/(\d+)/eliminar$#', $ruta, $m) && $metodo === 'POST':
            (new CarpetaController())->eliminar((int) $m[1]);
            break;
        case preg_match('#^api/carpetas/(\d+)/renombrar$#', $ruta, $m) && $metodo === 'POST':
            (new CarpetaController())->renombrar((int) $m[1]);
            break;

        // ---------- API: Documentos ----------
        case $ruta === 'api/documentos' && $metodo === 'POST':
            (new DocumentoController())->subir();
            break;
        case $ruta === 'api/documentos' && $metodo === 'GET':
            (new DocumentoController())->listarTodos();
            break;
        case preg_match('#^api/documentos/carpeta/(\d+)$#', $ruta, $m) && $metodo === 'GET':
            (new DocumentoController())->listarPorCarpeta((int) $m[1]);
            break;
        case preg_match('#^api/documentos/(\d+)$#', $ruta, $m) && $metodo === 'GET':
            (new DocumentoController())->detalle((int) $m[1]);
            break;
        case preg_match('#^api/documentos/(\d+)/descargar$#', $ruta, $m):
            (new DocumentoController())->descargar((int) $m[1]);
            break;
        case preg_match('#^api/documentos/(\d+)/eliminar$#', $ruta, $m) && $metodo === 'POST':
            (new DocumentoController())->eliminar((int) $m[1]);
            break;

        // ---------- API: Búsqueda y consulta ----------
        case $ruta === 'api/buscar' && $metodo === 'GET':
            (new BusquedaController())->buscarTexto();
            break;
        case $ruta === 'api/consulta' && $metodo === 'POST':
            (new BusquedaController())->consultarLenguajeNatural();
            break;

        // ---------- API: Dashboard y logs ----------
        case $ruta === 'api/dashboard' && $metodo === 'GET':
            (new DashboardController())->indicadores();
            break;
        case $ruta === 'api/logs' && $metodo === 'GET':
            (new DashboardController())->logs();
            break;

        default:
            http_response_code(404);
            echo '404 - Ruta no encontrada: ' . htmlspecialchars($ruta);
    }
} catch (Throwable $e) {
    http_response_code(500);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo 'Error: ' . $e->getMessage();
    } else {
        echo 'Ocurrió un error inesperado. Intenta nuevamente más tarde.';
    }
}
