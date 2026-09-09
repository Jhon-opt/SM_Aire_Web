<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'air_monitor');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_PATH', __DIR__);
$__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('BASE_URL', rtrim(($__isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']), '/') ?: '/');
unset($__isHttps);

define('API_URL', rtrim(getenv('API_URL') ?: 'https://calidad-aire-p.onrender.com', '/'));

// Modo de datos: 'api' (API externa), 'db' (MySQL local) o 'fake' (datos de prueba).
// Se puede fijar con la variable de entorno APP_MODE (o SetEnv en .htaccess).
// En esta rama el default es 'db' (deploy cPanel con MySQL local).
// Compatibilidad: API_MODE=api => 'api', API_MODE=false => 'fake'.
$__appMode = getenv('APP_MODE');
if ($__appMode === false || $__appMode === '') {
    $__apiMode = getenv('API_MODE');
    $__appMode = ($__apiMode === 'api') ? 'api' : (($__apiMode === 'false') ? 'fake' : 'db');
}
unset($__apiMode);
define('APP_MODE', $__appMode);
define('API_MODE', APP_MODE === 'api');
define('FAKE_MODE', APP_MODE === 'fake');
unset($__appMode);
define('ITEMS_PER_PAGE', 100);
define('REFRESH_INTERVAL', 60);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
