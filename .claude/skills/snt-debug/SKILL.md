---
name: snt-debug
description: >
  Полный цикл отладки раздела сайта СНТ «Берёзка»: анализ → фикс → тест → контроль.
  ИСПОЛЬЗОВАТЬ когда: «отладь раздел», «раздел не работает», «исправь и проверь»,
  «доделай объявления/голосования/финансы/бюджет», «раздел одинаков у всех»,
  «сравни со шаблоном», «добавь виджет», «обнови дашборд».
  Включает актуальную карту компонентов, дизайн-систему и типичные баги.
---

# Скил: Полный цикл отладки раздела СНТ

## Карта проекта (актуально на 2026-06-05)

### Компоненты
```
src/components/
├── ClosedPages/
│   ├── DashboardPage.tsx     — личный кабинет члена (профиль + разделы + бюджет + объявления)
│   ├── FinancesPage.tsx       — взносы + расходы (табы)
│   ├── ClassifiedsPage.tsx    — доска объявлений ✓ ГОТОВО
│   ├── VotesPage.tsx          — голосования
│   ├── ProtocolsPage.tsx      — документы/протоколы
│   └── DiscussionsPage.tsx    — связь/обсуждения (не тестировалось)
├── Admin/
│   ├── AdminDashboard.tsx     — KPI + взносы + финансы + объявления + собрания
│   ├── AdminFinances.tsx      — взносы (табы: взносы/расходы)
│   ├── AdminBudget.tsx        — статьи бюджета + расходы
│   ├── AdminVotes.tsx         — управление голосованиями
│   ├── AdminRegistry.tsx      — реестр членов
│   └── AdminModerationPage.tsx — заявки на вступление
└── hooks/
    ├── useAnnouncements.ts    ✓ ИСПРАВЛЕН
    ├── useUser.ts             ✓ ИСПРАВЛЕН
    ├── useFees.ts             — взносы по участкам
    ├── useBudget.ts           — статьи бюджета и расходы
    └── useAnnouncements.ts    ✓ ГОТОВ
```

### API файлы
```
api/
├── auth.php           — авторизация
├── users.php          — профили
├── announcements.php  ✓ ИСПРАВЛЕН (кодировка, поля, ENUM)
├── fees.php           — взносы по участкам
├── finances.php       — финансовые операции
├── votes.php          — голосования
├── documents.php      — документы
├── budget.php         — статьи бюджета и расходы (GET/POST/PUT/DELETE)
├── meetings.php       — собрания (GET/POST/DELETE)
├── registrations.php  — заявки
├── db.php             ✓ ИСПРАВЛЕН (SET NAMES utf8mb4)
└── config.php / config.local.php
```

### Таблицы БД
```
users, roles, sessions
announcements          ✓ ENUM добавлен 'wanted'
fee_types, fee_payments
finances
votes, vote_options, vote_results
documents
budget_items           — статьи бюджета (name, planned_amount, year)
budget_expenses        — фактические расходы по статьям
meetings               — собрания (date, topic, location)
registrations
```

## Порядок работы при отладке раздела

### 1. Анализ (читать перед правками)
- Компонент в `src/components/`
- Хук в `src/hooks/`
- API в `api/`
- Схема таблицы в `database.sql`

### 2. Чеклист типичных проблем
- [ ] API: JOIN с `users` для `author_id`, `phone`, `address`?
- [ ] Кодировка: `db.php` содержит `SET NAMES utf8mb4`?
- [ ] ENUM в БД содержит нужные значения?
- [ ] Адрес участка парсится через `.replace(/\D/g,'')` перед `parseInt`?
- [ ] Права доступа в PHP для каждой роли (`role_id`)?
- [ ] `bind_param` типы соответствуют данным (`i` для числа, `s` для строки)?
- [ ] `get_result()` НЕ вызывается внутри while-цикла?
- [ ] Стиль карточек соответствует дизайн-системе?
- [ ] Виджет раздела есть на стартовых страницах?

### 3. Дизайн-система

CSS-классы (`src/index.css`):
- `glass panel` — стеклянная секция
- `card panel` — карточка на бумаге
- `btn primary` / `btn ghost` / `btn danger sm` / `btn sm`
- `pill good/warn/bad dot` — статусные бейджи
- `mono` — моноширинный (даты, номера, коды)
- `field` — обёртка формы (label + input)
- `stagger` / `fade-up` — анимации
- `grid-2` / `grid-3` / `grid-4` — сетки

CSS-переменные:
```css
--ink / --ink-soft / --ink-mute   /* текст */
--bg / --bg-warm / --bg-soft / --paper  /* фоны */
--accent / --accent-deep          /* акцентный коричневый */
--good / --warn / --bad           /* статусные цвета */
--line / --line-soft              /* разделители */
--shadow-1 / --shadow-2           /* тени */
--radius / --radius-sm            /* скругления */
```

### 4. Виджеты на стартовых страницах

После добавления нового раздела — добавить виджет на:
- `DashboardPage.tsx` — для членов СНТ (паттерн: last 3 items + кнопка «Все →»)
- `AdminDashboard.tsx` — для председателя (KpiCard + список последних записей)

### 5. Синхронизация PHP
```powershell
Copy-Item "D:\Проекты claude\сайт снт\snt-berezka — копия (2)\api\*.php" "C:\xampp\htdocs\api\" -Force
```

### 6. Проверка TypeScript
```powershell
cd "D:\Проекты claude\сайт снт\snt-berezka — копия (2)"
npx tsc --noEmit
```

### 7. Запуск тестирования
Использовать `/snt-test` для параллельного контроля.

## Критичные баги (знать заранее)

| Симптом | Причина | Лечение |
|---------|---------|---------|
| Кириллица `??????` | `db.php` без `SET NAMES utf8mb4` | 3 строки в `Database::connect()` |
| Участок №0 везде | `parseInt("Участок №47")` = NaN | `.replace(/\D/g,'')` перед parseInt |
| 401 на все запросы | Apache стрипает Authorization | `.htaccess` с RewriteRule |
| `get_result()` в цикле | Результат не сохранён | `$result = $stmt->get_result()` до цикла |
| Неверный bind_param | `'s'` вместо `'i'` для числа | Исправить строку типов + `(int)` каст |
| Логин → /dashboard для всех | LoginPage не проверяет роль | `signIn()` возвращает `{ isAdmin }` |
