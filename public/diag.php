<?php
// Защита: удали этот файл после настройки сервера
define('BASE_PATH', dirname(__DIR__));

// Загружаем .env.local если есть
function _loadEnvDiag(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}
_loadEnvDiag(BASE_PATH . '/.env.local');

$host = getenv('DB_HOST') ?: 'localhost';
$name = getenv('DB_NAME') ?: 'snt_berezka';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

function ok(string $msg): string {
    return '<tr><td>' . htmlspecialchars($msg) . '</td><td style="color:#22c55e;font-weight:bold">✓ OK</td></tr>';
}
function fail(string $msg, string $detail = ''): string {
    return '<tr><td>' . htmlspecialchars($msg) . ($detail ? '<br><small style="color:#888">' . htmlspecialchars($detail) . '</small>' : '') . '</td><td style="color:#ef4444;font-weight:bold">✗ FAIL</td></tr>';
}
function info(string $label, string $val): string {
    return '<tr><td>' . htmlspecialchars($label) . '</td><td style="color:#60a5fa">' . htmlspecialchars($val) . '</td></tr>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Диагностика СНТ</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
  h1 { color: #60a5fa; }
  h2 { color: #94a3b8; margin-top: 2rem; border-bottom: 1px solid #334155; padding-bottom: .5rem; }
  table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
  td { padding: .5rem .75rem; border-bottom: 1px solid #1e293b; vertical-align: top; }
  td:first-child { width: 70%; }
  .warn { color: #f59e0b; font-weight: bold; }
  .box { background: #1e293b; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
  .delete-notice { background: #7f1d1d; border: 1px solid #dc2626; border-radius: 6px; padding: 1rem; margin-bottom: 2rem; color: #fca5a5; }
</style>
</head>
<body>
<h1>Диагностика сайта СНТ «Берёзка»</h1>
<div class="delete-notice">⚠️ Удали этот файл после настройки! <code>public/diag.php</code></div>

<div class="box">
<h2>Окружение</h2>
<table>
<?= info('PHP версия', PHP_VERSION) ?>
<?= info('Сервер', $_SERVER['SERVER_SOFTWARE'] ?? 'неизвестно') ?>
<?= info('Путь к проекту', BASE_PATH) ?>
<?= info('Текущий файл', __FILE__) ?>
<?= info('document_root', $_SERVER['DOCUMENT_ROOT'] ?? '—') ?>
</table>
</div>

<div class="box">
<h2>PHP расширения</h2>
<table>
<?= extension_loaded('mysqli') ? ok('mysqli') : fail('mysqli', 'Нужно включить в php.ini') ?>
<?= extension_loaded('pdo') ? ok('PDO') : fail('PDO') ?>
<?= extension_loaded('pdo_mysql') ? ok('PDO MySQL') : fail('PDO MySQL') ?>
<?= extension_loaded('mbstring') ? ok('mbstring') : fail('mbstring') ?>
<?= extension_loaded('json') ? ok('json') : fail('json') ?>
<?= extension_loaded('session') ? ok('session') : fail('session') ?>
<?= extension_loaded('fileinfo') ? ok('fileinfo') : fail('fileinfo') ?>
</table>
</div>

<div class="box">
<h2>Конфигурация БД</h2>
<table>
<?= info('DB_HOST', $host) ?>
<?= info('DB_NAME', $name) ?>
<?= info('DB_USER', $user) ?>
<?= info('DB_PASS', $pass ? str_repeat('*', strlen($pass)) : '(пусто — не задан!)') ?>
<?= info('.env.local', file_exists(BASE_PATH . '/.env.local') ? 'найден ✓' : 'НЕ найден — используются дефолты') ?>
</table>
</div>

<div class="box">
<h2>Подключение к MySQL</h2>
<table>
<?php
$conn = @new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    echo fail('Подключение к БД', 'Ошибка #' . $conn->connect_errno . ': ' . $conn->connect_error);
} else {
    echo ok('Подключение к БД (' . $host . ')');
    echo info('Версия MySQL', $conn->server_info);

    // Проверяем таблицы
    $tables = ['users','announcements','votes','finances','tickets','meetings','documents','notifications'];
    $result = $conn->query("SHOW TABLES");
    $existing = [];
    while ($row = $result->fetch_array()) $existing[] = $row[0];

    foreach ($tables as $t) {
        if (in_array($t, $existing)) {
            $cnt = $conn->query("SELECT COUNT(*) as c FROM `$t`")->fetch_assoc()['c'];
            echo ok("Таблица `$t` ($cnt записей)");
        } else {
            echo fail("Таблица `$t`", 'не существует — нужен импорт дампа');
        }
    }
    $conn->close();
}
?>
</table>
</div>

<div class="box">
<h2>Файловая система</h2>
<table>
<?php
$paths = [
    BASE_PATH . '/public'           => 'public/',
    BASE_PATH . '/public/uploads'   => 'public/uploads/ (запись)',
    BASE_PATH . '/src'              => 'src/',
    BASE_PATH . '/templates'        => 'templates/',
    BASE_PATH . '/config'           => 'config/',
    BASE_PATH . '/public/.htaccess' => 'public/.htaccess',
    BASE_PATH . '/bootstrap.php'    => 'bootstrap.php',
    BASE_PATH . '/router.php'       => 'router.php',
];
foreach ($paths as $path => $label) {
    if (!file_exists($path)) {
        echo fail($label, 'не найден');
    } elseif (is_dir($path) && strpos($label, 'запись') !== false) {
        echo is_writable($path) ? ok($label) : fail($label, 'нет прав на запись — chmod 755');
    } else {
        echo ok($label);
    }
}
?>
</table>
</div>

<div class="box">
<h2>PHP настройки</h2>
<table>
<?= info('display_errors', ini_get('display_errors') ?: 'off') ?>
<?= info('error_reporting', ini_get('error_reporting')) ?>
<?= info('upload_max_filesize', ini_get('upload_max_filesize')) ?>
<?= info('post_max_size', ini_get('post_max_size')) ?>
<?= info('max_execution_time', ini_get('max_execution_time') . 'с') ?>
<?= info('memory_limit', ini_get('memory_limit')) ?>
<?= info('session.save_path', ini_get('session.save_path') ?: '(дефолт)') ?>
</table>
</div>

<div class="box">
<h2>Сессии</h2>
<table>
<?php
session_start();
$_SESSION['diag_test'] = 'ok';
echo isset($_SESSION['diag_test']) ? ok('Сессии работают') : fail('Сессии не работают');
session_write_close();
?>
</table>
</div>

<p style="color:#475569;margin-top:2rem">Сгенерировано: <?= date('Y-m-d H:i:s') ?> | <?= PHP_SAPI ?></p>
</body>
</html>
