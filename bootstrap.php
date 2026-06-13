<?php
define('BASE_PATH', __DIR__);
define('SITE_NAME', 'СНТ «Берёзка»');
define('PLOT_COUNT', 61);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($class) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils.php';
