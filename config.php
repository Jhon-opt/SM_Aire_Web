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

define('API_MODE', getenv('API_MODE') !== 'false');
define('API_URL', rtrim(getenv('API_URL') ?: 'https://calidad-aire-p.onrender.com', '/'));
define('FAKE_MODE', !API_MODE);
define('ITEMS_PER_PAGE', 100);
define('REFRESH_INTERVAL', 60);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
