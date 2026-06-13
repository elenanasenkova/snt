<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', __DIR__);

function _loadEnvDiag($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $k = trim($parts[0]);
        $v = isset($parts[1]) ? trim($parts[1]) : '';
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}
_loadEnvDiag(BASE_PATH . '/.env.local');

$host = getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost';
$name = getenv('DB_NAME') ? getenv('DB_NAME') : 'snt_berezka';
$user = getenv('DB_USER') ? getenv('DB_USER') : 'root';
$pass = getenv('DB_PASS') ? getenv('DB_PASS') : '';

function row_ok($msg) {
    return '<tr><td>' . htmlspecialchars($msg) . '</td><td style="color:#22c55e;font-weight:bold">OK</td></tr>';
}
function row_fail($msg, $detail = '') {
    $d = $detail ? '<br><small style="color:#aaa">' . htmlspecialchars($detail) . '</small>' : '';
    return '<tr><td>' . htmlspecialchars($msg) . $d . '</td><td style="color:#ef4444;font-weight:bold">FAIL</td></tr>';
}
function row_info($label, $val) {
    return '<tr><td>' . htmlspecialchars($label) . '</td><td style="color:#60a5fa">' . htmlspecialchars((string)$val) . '</td></tr>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Диагностика СНТ</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:2rem}
h1{color:#60a5fa}h2{color:#94a3b8;margin-top:2rem;border-bottom:1px solid #334155;padding-bottom:.4rem}
table{width:100%;border-collapse:collapse;margin-top:.8rem}
td{padding:.4rem .7rem;border-bottom:1px solid #1e293b;vertical-align:top}
td:first-child{width:65%}
.box{background:#1e293b;border-radius:8px;padding:1.2rem;margin-bottom:1rem}
.warn{background:#7f1d1d;border:1px solid #dc2626;border-radius:6px;padding:.8rem;margin-bottom:1rem;color:#fca5a5}
.info-box{background:#1e3a5f;border:1px solid #3b82f6;border-radius:6px;padding:.8rem;margin-bottom:1rem;color:#bfdbfe}
</style>
</head>
<body>
<h1>Диагностика СНТ «Берёзка»</h1>
<div class="warn">Удали этот файл после настройки: <code>diag.php</code></div>

<div class="box">
<h2>Окружение</h2>
<table>
<?= row_info('PHP версия', PHP_VERSION) ?>
<?= row_info('Нужна версия', '7.1+') ?>
<?= row_info('PHP OK', version_compare(PHP_VERSION, '7.1.0', '>=') ? 'ДА' : 'НЕТ — смени в панели Beget!') ?>
<?= row_info('Сервер', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '—') ?>
<?= row_info('DOCUMENT_ROOT', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '—') ?>
<?= row_info('Путь файла', __FILE__) ?>
<?= row_info('BASE_PATH', BASE_PATH) ?>
</table>
</div>

<div class="box">
<h2>Расширения PHP</h2>
<table>
<?= extension_loaded('mysqli') ? row_ok('mysqli') : row_fail('mysqli', 'Включи в настройках PHP на Beget') ?>
<?= extension_loaded('mbstring') ? row_ok('mbstring') : row_fail('mbstring') ?>
<?= extension_loaded('json') ? row_ok('json') : row_fail('json') ?>
<?= extension_loaded('session') ? row_ok('session') : row_fail('session') ?>
<?= extension_loaded('pdo_mysql') ? row_ok('pdo_mysql') : row_fail('pdo_mysql') ?>
</table>
</div>

<div class="box">
<h2>Конфигурация БД</h2>
<table>
<?= row_info('DB_HOST', $host) ?>
<?= row_info('DB_NAME', $name) ?>
<?= row_info('DB_USER', $user) ?>
<?= row_info('DB_PASS', $pass ? str_repeat('*', min(strlen($pass), 8)) : '(не задан!)') ?>
<?= row_info('.env.local', file_exists(BASE_PATH . '/.env.local') ? 'НАЙДЕН' : 'НЕ найден (нужно создать!)') ?>
</table>
</div>

<div class="box">
<h2>Подключение к MySQL</h2>
<table>
<?php
if (!extension_loaded('mysqli')) {
    echo row_fail('mysqli не загружен', 'Включи расширение mysqli в панели Beget');
} else {
    $conn = @new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        echo row_fail('Подключение', '#' . $conn->connect_errno . ': ' . $conn->connect_error);
    } else {
        echo row_ok('Подключение к ' . $host);
        echo row_info('MySQL версия', $conn->server_info);
        $tables = array('users','announcements','votes','finances','tickets','meetings','documents','notifications');
        $res = $conn->query("SHOW TABLES");
        $existing = array();
        while ($r = $res->fetch_array()) $existing[] = $r[0];
        foreach ($tables as $t) {
            if (in_array($t, $existing)) {
                $cnt = $conn->query("SELECT COUNT(*) c FROM `$t`")->fetch_assoc();
                echo row_ok('Таблица ' . $t . ' (' . $cnt['c'] . ' строк)');
            } else {
                echo row_fail('Таблица ' . $t, 'отсутствует — импортируй дамп');
            }
        }
        $conn->close();
    }
}
?>
</table>
</div>

<div class="box">
<h2>Файлы проекта</h2>
<table>
<?php
$check = array(
    'index.php'          => 'index.php (точка входа)',
    '.htaccess'          => '.htaccess (корень)',
    'bootstrap.php'      => 'bootstrap.php',
    'router.php'         => 'router.php',
    'config/database.php'=> 'config/database.php',
    'src'                => 'src/ (папка)',
    'templates'          => 'templates/ (папка)',
    'public'             => 'public/ (папка)',
    'public/uploads'     => 'public/uploads/ (запись)',
);
foreach ($check as $rel => $label) {
    $full = BASE_PATH . '/' . $rel;
    if (!file_exists($full)) {
        echo row_fail($label, 'не найден');
    } elseif ($rel === 'public/uploads') {
        echo is_writable($full) ? row_ok($label) : row_fail($label, 'нет прав — chmod 755');
    } else {
        echo row_ok($label);
    }
}
?>
</table>
</div>

<div class="box">
<h2>Сессии</h2>
<table>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['diag'] = 1;
echo isset($_SESSION['diag']) ? row_ok('Сессии работают') : row_fail('Сессии не работают');
session_write_close();
?>
</table>
</div>

<p style="color:#475569;margin-top:1.5rem">
  <?= date('Y-m-d H:i:s') ?> | PHP <?= PHP_VERSION ?> | <?= PHP_SAPI ?>
</p>
</body>
</html>
