<?php
// Защита: удали этот файл после настройки сервера
define('BASE_PATH', __DIR__);

function _loadEnvDiag($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $k = trim($parts[0]);
        $v = trim($parts[1]);
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
    return '<tr><td>' . htmlspecialchars($msg) . '</td><td style="color:#22c55e;font-weight:bold">&#10003; OK</td></tr>';
}
function row_fail($msg, $detail = '') {
    return '<tr><td>' . htmlspecialchars($msg) . ($detail ? '<br><small style="color:#888">' . htmlspecialchars($detail) . '</small>' : '') . '</td><td style="color:#ef4444;font-weight:bold">&#10007; FAIL</td></tr>';
}
function row_info($label, $val) {
    return '<tr><td>' . htmlspecialchars($label) . '</td><td style="color:#60a5fa">' . htmlspecialchars($val) . '</td></tr>';
}

$php_ok = version_compare(PHP_VERSION, '7.1.0', '>=');
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
  .box { background: #1e293b; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
  .notice { border-radius: 6px; padding: 1rem; margin-bottom: 1rem; }
  .notice-red { background: #7f1d1d; border: 1px solid #dc2626; color: #fca5a5; }
  .notice-yellow { background: #78350f; border: 1px solid #f59e0b; color: #fde68a; }
</style>
</head>
<body>
<h1>Диагностика сайта СНТ «Берёзка»</h1>
<div class="notice notice-red">&#9888; Удали этот файл после настройки! <code>diag.php</code></div>

<?php if (!$php_ok): ?>
<div class="notice notice-yellow">
  &#9888; <strong>PHP <?= PHP_VERSION ?> — слишком старый!</strong><br>
  Сайт требует PHP 7.1+. В панели Beget: Сайты → домен → PHP → выбери 8.1 или 7.4 → Сохранить.
</div>
<?php endif; ?>

<div class="box">
<h2>Окружение</h2>
<table>
<?= row_info('PHP версия', PHP_VERSION . ($php_ok ? ' ✓' : ' — нужна 7.1+!')) ?>
<?= row_info('Сервер', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'неизвестно') ?>
<?= row_info('Путь к проекту', BASE_PATH) ?>
<?= row_info('Текущий файл', __FILE__) ?>
<?= row_info('DOCUMENT_ROOT', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '—') ?>
</table>
</div>

<div class="box">
<h2>PHP расширения</h2>
<table>
<?= extension_loaded('mysqli') ? row_ok('mysqli') : row_fail('mysqli', 'Нужно включить в php.ini') ?>
<?= extension_loaded('pdo') ? row_ok('PDO') : row_fail('PDO') ?>
<?= extension_loaded('pdo_mysql') ? row_ok('PDO MySQL') : row_fail('PDO MySQL') ?>
<?= extension_loaded('mbstring') ? row_ok('mbstring') : row_fail('mbstring') ?>
<?= extension_loaded('json') ? row_ok('json') : row_fail('json') ?>
<?= extension_loaded('session') ? row_ok('session') : row_fail('session') ?>
</table>
</div>

<div class="box">
<h2>Конфигурация БД</h2>
<table>
<?= row_info('DB_HOST', $host) ?>
<?= row_info('DB_NAME', $name) ?>
<?= row_info('DB_USER', $user) ?>
<?= row_info('DB_PASS', $pass ? str_repeat('*', strlen($pass)) : '(пусто!)') ?>
<?= row_info('.env.local', file_exists(BASE_PATH . '/.env.local') ? 'найден ✓' : 'НЕ найден — используются дефолты') ?>
</table>
</div>

<div class="box">
<h2>Подключение к MySQL</h2>
<table>
<?php
$conn = @new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    echo row_fail('Подключение к БД', 'Ошибка #' . $conn->connect_errno . ': ' . $conn->connect_error);
} else {
    echo row_ok('Подключение к БД (' . $host . ')');
    echo row_info('Версия MySQL', $conn->server_info);
    $tables = array('users','announcements','votes','finances','tickets','meetings','documents','notifications');
    $result = $conn->query("SHOW TABLES");
    $existing = array();
    while ($row = $result->fetch_array()) $existing[] = $row[0];
    foreach ($tables as $t) {
        if (in_array($t, $existing)) {
            $cnt = $conn->query("SELECT COUNT(*) as c FROM `$t`")->fetch_assoc();
            echo row_ok("Таблица `$t` (" . $cnt['c'] . " записей)");
        } else {
            echo row_fail("Таблица `$t`", 'не существует — нужен импорт дампа');
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
$paths = array(
    BASE_PATH . '/public'           => 'public/',
    BASE_PATH . '/public/uploads'   => 'public/uploads/ (запись)',
    BASE_PATH . '/src'              => 'src/',
    BASE_PATH . '/templates'        => 'templates/',
    BASE_PATH . '/config'           => 'config/',
    BASE_PATH . '/.htaccess'        => '.htaccess (корень)',
    BASE_PATH . '/bootstrap.php'    => 'bootstrap.php',
    BASE_PATH . '/router.php'       => 'router.php',
    BASE_PATH . '/index.php'        => 'index.php (корень)',
);
foreach ($paths as $path => $label) {
    if (!file_exists($path)) {
        echo row_fail($label, 'не найден');
    } elseif (is_dir($path) && strpos($label, 'запись') !== false) {
        echo is_writable($path) ? row_ok($label) : row_fail($label, 'нет прав на запись — chmod 755');
    } else {
        echo row_ok($label);
    }
}
?>
</table>
</div>

<div class="box">
<h2>PHP настройки</h2>
<table>
<?= row_info('display_errors', ini_get('display_errors') ? ini_get('display_errors') : 'off') ?>
<?= row_info('upload_max_filesize', ini_get('upload_max_filesize')) ?>
<?= row_info('post_max_size', ini_get('post_max_size')) ?>
<?= row_info('max_execution_time', ini_get('max_execution_time') . 'с') ?>
<?= row_info('memory_limit', ini_get('memory_limit')) ?>
</table>
</div>

<p style="color:#475569;margin-top:2rem">Сгенерировано: <?= date('Y-m-d H:i:s') ?></p>
</body>
</html>
