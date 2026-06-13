---
name: snt-sync
description: >
  Синхронизация PHP-файлов сайта СНТ «Берёзка» между папкой проекта и XAMPP htdocs.
  ИСПОЛЬЗОВАТЬ когда: «синхронизируй», «скопируй PHP», «обнови сервер», после любых правок PHP,
  перед тестированием, «php не применяется», «изменения не видны».
  Также запускать автоматически после каждого изменения файлов в папке api/.
---

# Скил: Синхронизация PHP → XAMPP

## Зачем это нужно

PHP-файлы существуют в двух местах:
- **Проект:** `D:\Проекты claude\сайт снт\snt-berezka — копия (2)\api\`
- **XAMPP htdocs:** `C:\xampp\htdocs\api\`

Apache читает только из htdocs. После изменения файла в проекте — нужно скопировать в htdocs, иначе изменения не применятся.

## Команды синхронизации

### Один файл
```powershell
Copy-Item "D:\Проекты claude\сайт снт\snt-berezka — копия (2)\api\ИМЯ.php" "C:\xampp\htdocs\api\ИМЯ.php" -Force
```

### Все PHP-файлы разом
```powershell
Copy-Item "D:\Проекты claude\сайт снт\snt-berezka — копия (2)\api\*.php" "C:\xampp\htdocs\api\" -Force
Write-Host "Sync OK"
```

### Проверить что синхронизировалось
```powershell
Get-ChildItem "C:\xampp\htdocs\api\*.php" | Select-Object Name, LastWriteTime | Sort-Object LastWriteTime -Descending
```

## Файлы API (полный список)

| Файл | Назначение |
|------|-----------|
| `db.php` | Подключение к MySQL — КРИТИЧНЫЙ, менять осторожно |
| `config.php` | Настройки БД и сайта |
| `auth.php` | Авторизация, логин, выход |
| `users.php` | Профили пользователей |
| `announcements.php` | Доска объявлений |
| `fees.php` | Взносы и оплаты |
| `finances.php` | Финансовые операции |
| `votes.php` | Голосования |
| `documents.php` | Документы и протоколы |
| `reports.php` | Отчёты |
| `registrations.php` | Заявки на вступление |

## Запуск XAMPP

Если сервисы не запущены:
```powershell
# Apache
Start-Process "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden

# MySQL
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--standalone" -WindowStyle Hidden

# Проверка
Start-Sleep 2
netstat -an | Select-String ":80 " | Select-Object -First 1
netstat -an | Select-String ":3306 " | Select-Object -First 1
```

## Важные настройки db.php

В `db.php` внутри `Database::connect()` ОБЯЗАТЕЛЬНО должно быть:
```php
self::$connection->set_charset('utf8mb4');
self::$connection->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
self::$connection->query("SET CHARACTER SET 'utf8mb4'");
```

Без этого кириллица в базе сохраняется как `??????`.

## Локальный конфиг

Файл `C:\xampp\htdocs\api\config.local.php` содержит локальные настройки БД (не в проекте — только в htdocs):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'snt_berezka');
define('SITE_URL', 'http://localhost:5173');
```

Этот файл НЕ синхронизировать из проекта — он только локальный.
