<?php

require_once __DIR__ . '/config.php';

if (FAKE_MODE) {
    require_once __DIR__ . '/includes/fake_data.php';
} else {
    require_once __DIR__ . '/includes/Database.php';
}

require_once __DIR__ . '/includes/Router.php';
require_once __DIR__ . '/includes/functions.php';

require_once __DIR__ . '/models/Colegio.php';
require_once __DIR__ . '/models/Dispositivo.php';
require_once __DIR__ . '/models/Medicion.php';

require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/controllers/ExportController.php';

$router = new Router();

$router->get('dashboard', [new DashboardController(), 'index']);

$router->get('api/filtros', [new ApiController(), 'filtros']);
$router->get('api/ultimas', [new ApiController(), 'ultimas']);
$router->get('api/mediciones', [new ApiController(), 'mediciones']);
$router->get('api/estadisticas', [new ApiController(), 'estadisticas']);
$router->get('api/tabla', [new ApiController(), 'tabla']);

$router->get('export/csv', [new ExportController(), 'csv']);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_GET['url'] ?? '';

if (!$uri) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptDir !== '/' && str_starts_with($requestPath, $scriptDir)) {
        $requestPath = substr($requestPath, strlen($scriptDir));
    }
    $uri = trim($requestPath, '/');
}

$uri = $uri ?: 'dashboard';

try {
    $router->dispatch($method, $uri);
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    if (str_starts_with($uri, 'api/')) {
        jsonResponse(['error' => 'Error en la base de datos'], 500);
    } else {
        http_response_code(500);
        view('header', ['titulo' => 'Error']);
        echo '<div class="container"><div class="error-card"><h2>Error de conexión</h2><p>No se pudo conectar con la base de datos.</p></div></div>';
        view('footer');
    }
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    if (str_starts_with($uri, 'api/')) {
        jsonResponse(['error' => 'Error interno del servidor'], 500);
    } else {
        http_response_code(500);
        view('header', ['titulo' => 'Error']);
        echo '<div class="container"><div class="error-card"><h2>Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p></div></div>';
        view('footer');
    }
}
