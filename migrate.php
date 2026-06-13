<?php
if (php_sapi_name() !== 'cli') {
    die('Run from CLI only: php migrate.php' . PHP_EOL);
}
require_once __DIR__ . '/bootstrap.php';
$files = glob(__DIR__ . '/database/migrations/*.php');
if (empty($files)) {
    echo "No migrations found." . PHP_EOL;
    exit;
}
sort($files);
foreach ($files as $file) {
    echo "Running: " . basename($file) . "... ";
    require_once $file;
    echo "OK" . PHP_EOL;
}
echo "Done." . PHP_EOL;
