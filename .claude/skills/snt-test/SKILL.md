---
name: snt-test
description: >
  Параллельное тестирование сайта СНТ «Берёзка» через Workflow с агентами.
  ИСПОЛЬЗОВАТЬ ВСЕГДА когда нужно протестировать раздел сайта, проверить что-то после правок,
  убедиться что функция работает для разных ролей пользователей.
  Запускает минимум двух агентов одновременно — от имени разных ролей (член, председатель и т.д.).
  Шаблон: первый тест → фиксер ошибок (если нашлись) → контрольный тест.
  Триггеры: «протестируй», «проверь раздел», «убедись что работает», «запусти тест»,
  «проверь для всех пользователей», «отладь и проверь».
---

# Скил: Параллельное тестирование СНТ

## Что делает

Запускает Workflow с параллельными агентами, которые тестируют сайт от имени разных ролей пользователей одновременно. Шаблон: тест → фикс → контроль.

## Тестовые пользователи

| Роль | Email | Пароль | Стартовая страница |
|------|-------|--------|--------------------|
| Член СНТ | member@snt-berezka.ru | Test1238 | /dashboard |
| Председатель | chairman@snt-berezka.ru | Test1235 | /admin |
| Казначей | treasurer@snt-berezka.ru | Test1236 | /dashboard |
| Секретарь | secretary@snt-berezka.ru | Test1237 | /dashboard |
| Гость | guest@snt-berezka.ru | Test1239 | / |
| Админ | admin@snt-berezka.ru | Test1234 | /admin |

API base URL: `http://localhost/api`
Frontend: `http://localhost:5173`

## Как тестировать через API (PowerShell)

```powershell
# 1. Логин
$r = Invoke-RestMethod -Uri "http://localhost/api/auth.php" -Method POST `
  -ContentType "application/json; charset=UTF-8" `
  -Body '{"email":"member@snt-berezka.ru","password":"Test1238"}'
$token = $r.token

# 2. Запрос с токеном
$headers = @{"Authorization"="Bearer $token"; "Content-Type"="application/json; charset=UTF-8"}

# 3. GET запрос
Invoke-RestMethod -Uri "http://localhost/api/announcements.php?page=1" -Headers $headers

# 4. POST запрос (кириллица — через UTF-8 bytes!)
$body = [System.Text.Encoding]::UTF8.GetBytes('{"title":"Тест","content":"Текст","category":"info"}')
Invoke-RestMethod -Uri "http://localhost/api/announcements.php" -Method POST -Headers $headers -Body $body
```

**Важно для кириллицы:** всегда передавать тело как `[System.Text.Encoding]::UTF8.GetBytes(...)` иначе текст запишется как `??????`.

## Шаблон Workflow

```javascript
// Фаза 1: Параллельный тест двух ролей
const results = await parallel([
  () => agent(`
    Тест роли: member@snt-berezka.ru / Test1238
    Проверить: [что тестируем]
    API: http://localhost/api
    Использовать PowerShell + Invoke-RestMethod.
    Вернуть через StructuredOutput: { ok, issues, ... }
  `, { label: 'Тест: member', schema: SCHEMA }),

  () => agent(`
    Тест роли: chairman@snt-berezka.ru / Test1235
    Проверить: [что тестируем]
    API: http://localhost/api
    Вернуть через StructuredOutput: { ok, issues, ... }
  `, { label: 'Тест: chairman', schema: SCHEMA }),
]);

// Фаза 2: Фикс если есть баги
const allIssues = results.flatMap(r => r?.issues ?? []).filter(Boolean);
if (allIssues.length > 0) {
  await agent(`Исправь баги: ${allIssues.join('; ')}. Синхронизируй PHP в htdocs.`);
}

// Фаза 3: Контрольный тест
const control = await parallel([ /* те же агенты */ ]);
```

## Что проверять в тестах

- Авторизация: токен получен, нет 401/403
- CRUD: создание, чтение, удаление работают
- Кириллица в ответе: нет `??????` в полях title/content
- Поля в ответе: все ожидаемые поля присутствуют
- Права доступа: гость не может создавать, автор может удалять своё
- UI (через preview_snapshot): нужные элементы видны на странице

## Что НЕ делать

- Не слать скрины пользователю (`preview_screenshot`)
- Не ждать подтверждений — полная автономия
- Не тестировать только одну роль — минимум 2 параллельно
