---
name: snt-berezka
description: >
  Экспертные знания о проекте сайта СНТ «Берёзка» — стек, структура файлов, паттерны отладки,
  критические правила (PHP-синхронизация, кодировка, парсинг адресов, роли).
  ИСПОЛЬЗУЙ ЭТОТ СКИЛ при любой работе с сайтом СНТ: правке PHP или React-кода,
  отладке API, добавлении новых функций, вопросах о структуре проекта, запуске сервера.
  Триггеры: «сайт снт», «берёзка», «classifieds», «объявления сайта», «api/», «починить»,
  «не работает», «добавь на сайт», «исправь баг» в контексте этого проекта.
---

# СНТ «Берёзка» — проектное руководство

> ⚠️ **АКТУАЛЬНО (2026-06-12):** боевой сайт — **PHP-версия `berezka-php` на http://localhost:8080** (Apache). React-версия заморожена. Стек — PHP + mysqli + сессии (НЕ React, НЕ Vite, НЕ Bearer). Подробности и план — в `ТЗ_следующая_сессия.md` в корне berezka-php.

## Расположение и запуск

**Проект:** `D:\Проекты claude\сайт снт\berezka-php\` (DocumentRoot Apache → `berezka-php/public`).

**Запуск:** двойной клик `start-snt.bat` (стартует MySQL :3306 + Apache :80/:8080) → http://localhost:8080.
Остановка: `stop-snt.bat`. **MySQL обязателен** — иначе `Fatal error mysqli`.

```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--standalone" -WindowStyle Hidden
Start-Process "C:\xampp\apache\bin\httpd.exe" -WindowStyle Hidden
# Проверка: Get-NetTCPConnection -LocalPort 8080,3306 -State Listen
```
Правки PHP применяются сразу (htdocs НЕ нужен — Apache читает berezka-php напрямую).

---

## Стек

| Слой | Технология |
|------|-----------|
| Приложение | PHP (MVC: `router.php` → `src/controllers` → `src/models` → `templates`) |
| Доступ к БД | **mysqli** (`src/services/DatabaseConnection.php`), НЕ PDO |
| Авторизация | PHP-сессии (`$_SESSION['user']`) + CSRF (`src/helpers/csrf.php`), НЕ Bearer |
| Стили | Кастомная дизайн-система `public/css/style.css` (классы .card/.panel/.btn/.pill/.field/.grid-*, токены --ink/--accent/--paper…) |
| БД | MySQL/MariaDB, база `snt_berezka` |
| Роли | хелперы в `utils.php`: `isAdmin`[1,2], `isBoard`[1,2,3,4], `canFinance`[1,2,3], `canMeetings`[1,2,4] |
| Хостинг (prod) | Beget — `nasenkova.store` / `snt.nasenkova.beget.tech` |

---

## Ключевые файлы

| Файл | Назначение |
|------|-----------|
| `src/api.ts` | **Единственный** API-клиент. НЕ использовать `src/services/api.ts` (помечен @deprecated) |
| `src/context/AuthContext.tsx` | Авторизация, роли, `signIn()` → `{isAdmin}` |
| `src/utils/roles.ts` | Константы ролей `ROLES`, `BOARD_ROLES` — никогда не хардкодить числа! |
| `src/hooks/useAnnouncements.ts` | Объявления: CRUD, mapRow |
| `src/hooks/useFees.ts` | Взносы и оплаты |
| `src/components/Admin/AdminDashboard.tsx` | Дашборд администратора — KPI, финансы, взносы, свет, собрания |
| `src/components/Admin/AdminMeetings.tsx` | Страница управления собраниями `/admin/meetings` |
| `api/config.local.php` | Локальный конфиг БД |
| `api/db.php` | Подключение к MySQL — обязан содержать `SET NAMES utf8mb4` |
| `api/meetings.php` | Собрания: GET/POST/DELETE, таблица `meetings`, авто-создаёт таблицу |

---

## 🔴 Критические правила

### 1. PHP → всегда синхронизировать в htdocs
После **любой** правки PHP-файла:
```powershell
Copy-Item "D:\Проекты claude\сайт снт\snt-berezka — копия (2)\api\ИМЯ.php" `
          "C:\xampp\htdocs\api\ИМЯ.php" -Force
```
Без этого изменения не применятся — Apache читает из htdocs, не из папки проекта.

### 2. Кодировка кириллицы
`api/db.php` обязан содержать после `set_charset()`:
```php
self::$connection->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
self::$connection->query("SET CHARACTER SET 'utf8mb4'");
```
Без этого INSERT с кириллицей даёт `???` в базе.

При PowerShell-запросах всегда указывать:
```powershell
-ContentType "application/json; charset=UTF-8"
-Body ([System.Text.Encoding]::UTF8.GetBytes('{"title":"Кириллица"}'))
```

### 3. Парсинг номера участка из address
Поле `users.address` содержит строку вида `"Участок №47"`.  
**Правильно** (везде — useUser.ts, AuthContext.tsx, useAnnouncements.ts):
```js
parseInt(address.replace(/\D/g, ''), 10) || 0
```
**Неправильно:** `parseInt(address)` → NaN → 0.

### 4. Роли — только через константы
```ts
import { ROLES, BOARD_ROLES } from '../utils/roles';
// ПРАВИЛЬНО:
if (roleName === ROLES.ADMIN) { ... }
// НЕПРАВИЛЬНО:
if (roleId === 1) { ... }
```

### 5. signIn() возвращает isAdmin
```ts
const { isAdmin } = await signIn(email, password);
navigate(isAdmin ? '/admin' : '/dashboard');
```

---

## Роли пользователей

| role_id | roleName | Описание |
|---------|----------|----------|
| 1 | admin | Системный администратор |
| 2 | chairman | Председатель (→ `/admin` после входа) |
| 3 | treasurer | Казначей |
| 4 | secretary | Секретарь |
| 5 | member | Член СНТ (→ `/dashboard` после входа) |
| 6 | guest | Гость (ограниченный доступ) |

`isAdmin` = true для role_id 1 и 2 (admin + chairman).

---

## Тестовые пользователи (только локально)

| Email | Пароль | Роль | plot (address) |
|-------|--------|------|----------------|
| admin@snt-berezka.ru | Test1234 | admin | Правление |
| chairman@snt-berezka.ru | Test1235 | chairman | Участок №1 |
| member@snt-berezka.ru | Test1238 | member | Участок №47 |
| guest@snt-berezka.ru | Test1239 | guest | — |

---

## Дизайн-система

Классы из `src/index.css` — использовать вместо инлайн-стилей где возможно:
- `.glass` / `.glass-deep` — стекломорфизм
- `.card` — карточка (paper + border + shadow)
- `.panel` — padding 28px + border-radius 14px
- `.btn`, `.btn.primary`, `.btn.ghost`, `.btn.danger`, `.btn.sm`
- `.pill`, `.pill.good`, `.pill.warn`, `.pill.bad`
- `.field` — обёртка для label + input
- `.grid-2`, `.grid-3`, `.grid-4` — сетки
- `.mono` — JetBrains Mono, 12px
- `.fade-up`, `.stagger` — анимации входа
- CSS-переменные: `--ink`, `--ink-soft`, `--ink-mute`, `--accent`, `--bg-warm`, `--paper`

---

## API — основные эндпоинты

```
POST /api/auth.php                    Логин → {token, user}
GET  /api/users.php?id=N              Профиль пользователя
GET  /api/announcements.php           Список объявлений
POST /api/announcements.php           Создать объявление {title, content, category}
GET  /api/fees.php?year=N             Взносы + платежи → {feeTypes, payments}
POST /api/votes.php                   Создать голосование (chairman/admin)
GET  /api/reports.php?year=N          Расходы → {totalExpenses, totalCollected, balance}
GET  /api/meetings.php?upcoming=1     Предстоящие собрания → {meetings[]}
POST /api/meetings.php                Создать собрание {meeting_date, topic, location}
DELETE /api/meetings.php?id=X         Удалить собрание
```

Заголовок авторизации: `Authorization: Bearer <token>` (из `sessionStorage('snt_token')`).

---

## Архитектура AdminDashboard — финансовая логика

**Важно:** электроэнергия («Свет») — транзитные деньги, **не входят в баланс СНТ**.

```
balance = feeSplit.regularCollected - finances.expenses
       ≠ totalCollected (который включает свет)
```

Разбивка вычисляется в дашборде из данных `fees.php`:
- `isElec = ft.name.toLowerCase().includes('свет')` → отделяет свет от целевых взносов
- `regularRequired = ft.amount * PLOT_COUNT` (61) — для целевых взносов с фиксированной суммой
- `electricRequired = SUM(fee_payments.required_amount > 0)` — индивидуальные суммы

Виджет электроэнергии показывает помесячно. `allPaid = paidCount === totalPlots` (только участки с ненулевым счётом).

---

## Частые ловушки

- **`get_result()` в цикле** — вызывать ОДИН раз, сохранять в переменную, затем `while ($row = $result->fetch_assoc())`
- **bind_param типы** — числовые поля: `'i'`, строки: `'s'`, float: `'d'`. Ошибка типа → данные не сохраняются или ошибка SQL
- **Preview server ID** — получать заново каждую сессию через `preview_start`, не хранить между сессиями
- **`src/services/api.ts`** — устаревший дубликат, не импортировать
- **ENUM в БД** — если фронт отправляет значение вне ENUM → MySQL в strict mode выдаст ошибку. Проверять через `ALTER TABLE ... SHOW CREATE TABLE`
