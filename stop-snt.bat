@echo off
chcp 65001 >nul
echo Останавливаю СНТ «Берёзка» (Apache + MySQL)...
taskkill /F /IM httpd.exe  >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
echo Готово.
timeout /t 2 /nobreak >nul