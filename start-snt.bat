@echo off
chcp 65001 >nul
title СНТ «Берёзка» — запуск (PHP-сайт на :8080)

echo.
echo == СНТ «Берёзка» — локальный запуск ==
echo.

if not exist "C:\xampp\apache\bin\httpd.exe" (
  echo [X] XAMPP не найден в C:\xampp — установите XAMPP.
  pause
  exit /b 1
)

echo Останавливаю старые процессы...
taskkill /F /IM httpd.exe  >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

echo Запуск MySQL (MariaDB)...
start "MySQL (СНТ)" "C:\xampp\mysql\bin\mysqld.exe" --standalone

echo Запуск Apache (PHP-сайт на :8080, API на :80)...
start "Apache (СНТ)" "C:\xampp\apache\bin\httpd.exe"

echo Ожидаю запуск служб...
timeout /t 5 /nobreak >nul

echo.
echo Открываю сайт: http://localhost:8080
start "" http://localhost:8080

echo.
echo  Сайт:        http://localhost:8080      (СНТ «Берёзка», PHP)
echo  phpMyAdmin:  http://localhost/phpmyadmin
echo.
echo  Тестовые входы:
echo    Председатель: chairman@snt-berezka.ru / Test1235
echo    Член СНТ:      member@snt-berezka.ru   / Test1238
echo.
echo  Остановить: закройте окна «Apache (СНТ)» и «MySQL (СНТ)»
echo              или запустите stop-snt.bat
echo.
pause