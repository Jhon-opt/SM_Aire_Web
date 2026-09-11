<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'air_monitor');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_PATH', __DIR__);
$__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('BASE_URL', rtrim(($__isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') ?: '/');
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

// Versión de la interfaz: se añade a las URLs de CSS/JS para invalidar la caché del navegador.
define('APP_VERSION', '1.1.0');

// Zona horaria.
// - Almacenamiento e ingesta: siempre UTC (la columna fecha_hora guarda UTC,
//   independientemente de la zona horaria del servidor).
// - Presentación: hora de Colombia (APP_TIMEZONE), tanto en PHP (gráficas,
//   Excel) como en el navegador (tarjetas, tabla).
date_default_timezone_set('UTC');
define('APP_TIMEZONE', 'America/Bogota');

// Segundos entre consultas del navegador para detectar mediciones nuevas
// (actualización en tiempo real de tarjetas, gráficas y tablas).
define('LIVE_POLL_SECONDS', 10);

// Mapa: vista predeterminada (Bogotá D.C.) cuando se abre el panel.
define('MAPA_CENTRO_LAT', 4.7110);
define('MAPA_CENTRO_LNG', -74.0721);
define('MAPA_ZOOM', 11);

// JSON: floats con la representación más corta posible
// (evita salidas como 0.6999999999999999555 en hostings con precisión alta).
ini_set('serialize_precision', '-1');
ini_set('precision', '14');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
