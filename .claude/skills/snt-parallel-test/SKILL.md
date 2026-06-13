---
name: snt-parallel-test
description: >
  Параллельное тестирование сайта СНТ «Берёзка» через Workflow — два и более агентов
  одновременно, от разных ролей (member + chairman). Автоматически находит баги,
  исправляет их и прогоняет контрольный тест.
  ИСПОЛЬЗУЙ ЭТОТ СКИЛ всегда когда нужно: протестировать функцию сайта, проверить
  что баг исправлен, убедиться что API работает корректно, провести приёмочное тестирование
  нового раздела. Триггеры: «протестируй», «проверь», «запусти тесты», «убедись что работает»,
  «контрольный прогон», «всё ли работает», «проверь после исправлений».
---

# Параллельное тестирование СНТ «Берёзка»

## Принцип работы

Всегда запускай **Workflow** с `parallel()` — минимум 2 агента одновременно:
- **Агент member** (member@snt-berezka.ru / Test1238) — тестирует функции члена СНТ
- **Агент chairman** (chairman@snt-berezka.ru / Test1235) — тестирует функции правления

После первого теста: если найдены ошибки → агент-фиксер → контрольный тест.

---

## Шаблон Workflow

```javascript
export const meta = {
  name: 'snt-test-ФУНКЦИЯ',
  description: 'Параллельный тест: ФУНКЦИЯ от двух ролей',
  phases: [
    { title: 'Тест', detail: 'Параллельно: member + chairman' },
    { title: 'Исправление', detail: 'Фикс найденных ошибок' },
    { title: 'Контроль', detail: 'Повторный тест после фиксов' },
  ],
};

const SCHEMA = {
  type: 'object',
  properties: {
    user: { type: 'string' },
    loginOk: { type: 'boolean' },
    actionOk: { type: 'boolean' },
    issues: { type: 'array', items: { type: 'string' } },
    ok: { type: 'boolean' },
  },
  required: ['user', 'loginOk', 'actionOk', 'ok'],
};

phase('Тест');
const results = await parallel([
  () => agent(`... инструкция для member ...`, { label: 'member', phase: 'Тест', schema: SCHEMA }),
  () => agent(`... инструкция для chairman ...`, { label: 'chairman', phase: 'Тест', schema: SCHEMA }),
]);

const issues = results.filter(Boolean).flatMap(r => r.issues ?? []);

phase('Исправление');
if (issues.length > 0) {
  await agent(`Исправь: ${issues.join('; ')}`, { label: 'фиксер', phase: 'Исправление' });
} else {
  log('Ошибок нет, исправления не нужны.');
}

phase('Контроль');
const control = await parallel([
  () => agent(`... контрольный тест member ...`, { label: 'контроль:member', phase: 'Контроль', schema: SCHEMA }),
  () => agent(`... контрольный тест chairman ...`, { label: 'контроль:chairman', phase: 'Контроль', schema: SCHEMA }),
]);

return { phase1: results, fixedIssues: issues, phase3: control };
```

---

## Инструкция агентам-тестировщикам

Каждый агент получает задачу в этом формате:

```
Ты тестируешь сайт СНТ «Берёзка» от имени [РОЛЬ].

Пользователь: [EMAIL] / [ПАРОЛЬ]
API: http://localhost/api

Шаги:
1. Авторизуйся через POST /api/auth.php
2. [КОНКРЕТНОЕ ДЕЙСТВИЕ — создать/получить/удалить]
3. Проверь результат: [КРИТЕРИИ ПРОВЕРКИ]

PowerShell:
$r = Invoke-RestMethod -Uri "http://localhost/api/auth.php" -Method POST \
  -ContentType "application/json; charset=UTF-8" \
  -Body '{"email":"[EMAIL]","password":"[ПАРОЛЬ]"}'
$token = $r.token

# Запрос с токеном:
Invoke-RestMethod -Uri "http://localhost/api/ENDPOINT.php" \
  -Method POST \
  -Headers @{"Authorization"="Bearer $token"; "Content-Type"="application/json; charset=UTF-8"} \
  -Body ([System.Text.Encoding]::UTF8.GetBytes('{"key":"Кириллица здесь"}'))

Верни через StructuredOutput.
```

---

## Критически важно в тестах

### Кодировка кириллицы
**Всегда** при POST-запросах с кириллицей:
```powershell
-ContentType "application/json; charset=UTF-8"
-Body ([System.Text.Encoding]::UTF8.GetBytes('{"title":"Заголовок"}'))
```
Без `[System.Text.Encoding]::UTF8.GetBytes()` PowerShell испортит кириллицу → `???` в базе.

### Что проверять в каждом тесте

| Проверка | Как |
|----------|-----|
| Логин работает | token не null и не пустой |
| Кириллица сохранилась | title в GET не содержит `?` |
| Нужные поля в ответе | проверить наличие id, author_id, created_at и т.д. |
| Права доступа | guest не может создавать, member не может делать то что только chairman |
| Редирект после входа | admin/chairman → `/admin`, остальные → `/dashboard` |

---

## Агент-фиксер

Когда найдены ошибки, агент-фиксер получает:

```
Ты исправляешь баги сайта СНТ «Берёзка».

Проект: D:\Проекты claude\сайт снт\snt-berezka — копия (2)\
PHP-файлы синхронизировать: Copy-Item "...\api\ФАЙЛ.php" "C:\xampp\htdocs\api\ФАЙЛ.php" -Force

Найденные проблемы:
1. [ПРОБЛЕМА]
2. [ПРОБЛЕМА]

Для каждой проблемы:
1. Прочитай соответствующий файл
2. Найди причину
3. Исправь
4. Если PHP — скопируй в htdocs

Верни через StructuredOutput: {fixesApplied: string[], remainingIssues: string[]}
```

---

## Итоговый отчёт

После тестов — краткая таблица:

| Что тестировалось | member | chairman | Исправлено |
|-------------------|--------|----------|-----------|
| Логин + редирект | ✅ | ✅ | — |
| Создание объявления | ✅ | ✅ | — |
| Кириллица | ✅ | ✅ | да (SET NAMES) |
| Номер участка | ✅ | ✅ | да (replace /\D/g) |

**Правило:** всегда 2+ параллельных агента. Никогда не тестировать последовательно то, что можно параллельно.
