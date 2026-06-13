-- ========================================
-- СНТ "Берёзка" - Структура базы данных MySQL
-- ЕДИНЫЙ ИСТОЧНИК ПРАВДЫ (схема). Обновлено 2026-06-13 (аудит, п.3).
--
-- Содержит ВСЕ таблицы, которые использует код berezka-php, включая
-- добавленные ранее миграциями: finances.fee_type_id (002),
-- tickets/ticket_replies (003), notifications (модель Notification),
-- fee_types.plot_scope (004).
--
-- Развёртывание с нуля:
--   1) mysql -u root snt_berezka < database/schema.sql
--   2) php migrate.php   (миграции идемпотентны — на чистой схеме просто no-op)
--
-- Дальше схему менять ТОЛЬКО нумерованными миграциями database/migrations/NNN_*.php,
-- затем переносить изменение сюда. Руками в phpMyAdmin — не менять.
-- ========================================

-- Удаляем старые таблицы (если есть)
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS votes_participants;
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS finances;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS user_roles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- ========================================
-- 1. ТАБЛИЦА РОЛЕЙ
-- ========================================
CREATE TABLE roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE COMMENT 'админ, председатель, казначей, секретарь, член, гость',
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Роли и должности в СНТ';

INSERT INTO roles (name, description) VALUES
  ('админ', 'Полный доступ к системе'),
  ('председатель', 'Возглавляет правление'),
  ('казначей', 'Управляет финансами'),
  ('секретарь', 'Ведёт протоколы и документы'),
  ('член', 'Обычный член СНТ'),
  ('гость', 'Неавторизованный пользователь');

-- ========================================
-- 2. ТАБЛИЦА ПОЛЬЗОВАТЕЛЕЙ (ЧЛЕНОВ СНТ)
-- ========================================
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL COMMENT 'bcrypt хеш пароля',
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255) COMMENT 'Адрес участка в СНТ',
  role_id INT NOT NULL DEFAULT 5 COMMENT 'ID роли (по умолчанию член)',
  status ENUM('active', 'inactive', 'pending') DEFAULT 'pending' COMMENT 'статус в системе',
  avatar_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX idx_email (email),
  INDEX idx_role (role_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Члены СНТ';

-- ========================================
-- 3. ТАБЛИЦА СЕССИЙ (ДЛЯ ЛОГИНА)
-- ========================================
CREATE TABLE sessions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE COMMENT 'JWT или сессионный токен',
  ip_address VARCHAR(45),
  user_agent TEXT,
  expires_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Активные сессии пользователей';

-- ========================================
-- 4. ТАБЛИЦА ЗАЯВОК НА РЕГИСТРАЦИЮ
-- ========================================
CREATE TABLE registrations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(120) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  address VARCHAR(255),
  message TEXT COMMENT 'Причина присоединения',
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  reviewed_by INT COMMENT 'ID админа, одобрившего заявку',
  reviewed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reviewed_by) REFERENCES users(id),
  INDEX idx_email (email),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заявки на регистрацию в СНТ';

-- ========================================
-- 5. ТАБЛИЦА ДОКУМЕНТОВ (Уставы, Протоколы и т.д.)
-- ========================================
CREATE TABLE documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category ENUM('устав', 'протокол', 'положение', 'смета', 'другое') DEFAULT 'другое',
  file_path VARCHAR(255) NOT NULL COMMENT 'Путь к файлу на сервере',
  file_size INT COMMENT 'Размер файла в байтах',
  mime_type VARCHAR(50) COMMENT 'application/pdf и т.д.',
  uploaded_by INT NOT NULL COMMENT 'ID пользователя, загрузившего',
  is_public BOOLEAN DEFAULT TRUE COMMENT 'Видят все или только члены',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (uploaded_by) REFERENCES users(id),
  INDEX idx_category (category),
  INDEX idx_uploaded (uploaded_by),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Документы: уставы, протоколы, сметы';

-- ========================================
-- 6. ТАБЛИЦА ФИНАНСОВ
-- ========================================
CREATE TABLE finances (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  type ENUM('income', 'expense', 'fee', 'payment', 'penalty', 'refund') DEFAULT 'fee',
  fee_type_id INT NULL DEFAULT NULL COMMENT 'Категория взноса, из которой трата (migration 002)',
  amount DECIMAL(10, 2) NOT NULL COMMENT 'Сумма в рублях',
  description VARCHAR(255),
  receipt_number VARCHAR(50) COMMENT 'Номер квитанции',
  attachment_path VARCHAR(500) NULL COMMENT 'Путь к файлу в uploads/',
  attachment_name VARCHAR(255) NULL COMMENT 'Оригинальное имя файла',
  status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
  due_date DATE,
  paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  INDEX idx_type (type),
  INDEX idx_fee_type_id (fee_type_id),
  INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Финансовые операции: взносы, платежи';

-- ========================================
-- 7. ТАБЛИЦА ГОЛОСОВАНИЙ
-- ========================================
CREATE TABLE votes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  started_at TIMESTAMP NULL,
  ended_at TIMESTAMP NULL,
  results_visible BOOLEAN DEFAULT FALSE COMMENT 'Видны ли результаты',
  FOREIGN KEY (created_by) REFERENCES users(id),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Голосования и решения';

-- Таблица вариантов голосования
CREATE TABLE votes_options (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vote_id INT NOT NULL,
  option_text VARCHAR(255) NOT NULL,
  vote_count INT DEFAULT 0,
  FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
  INDEX idx_vote (vote_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Варианты ответов в голосованиях';

-- Таблица голосов участников
CREATE TABLE votes_participants (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vote_id INT NOT NULL,
  user_id INT NOT NULL,
  option_id INT NOT NULL,
  voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vote_id) REFERENCES votes(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES votes_options(id) ON DELETE CASCADE,
  UNIQUE KEY unique_vote (vote_id, user_id) COMMENT 'Один голос на человека',
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Голоса участников';

-- ========================================
-- 8. ТАБЛИЦА ОБЪЯВЛЕНИЙ
-- ========================================
CREATE TABLE announcements (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  author_id INT NOT NULL,
  category ENUM('sale', 'buy', 'service', 'info', 'wanted') NOT NULL DEFAULT 'info',
  is_pinned BOOLEAN DEFAULT FALSE COMMENT 'Закреплённое объявление',
  status ENUM('draft', 'published', 'archived') DEFAULT 'published',
  views_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id),
  INDEX idx_category (category),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  FULLTEXT idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Объявления и новости СНТ';

-- ========================================
-- ТЕСТОВЫЕ ДАННЫЕ
-- ========================================

-- Тестовые пользователи (пароль для @snt.ru: "password123", для @snt-berezka.ru см. комментарий ниже)
-- Хеши сгенерированы через PHP: password_hash('...', PASSWORD_BCRYPT)
INSERT INTO users (email, password_hash, full_name, phone, address, role_id, status) VALUES
('admin@snt.ru', '$2y$10$cAG/CuPeigfMAcV3J4GJ6.ASAMP76ry6vdrn75p88zAdZ1zH8IjBu', 'Администратор', '+79001234567', 'Офис СНТ', 1, 'active'),
('chairman@snt.ru', '$2y$10$nXtx0J/LiguBxTGZB/aztO1rP48NUKCsCEbTFcRma8QqN55M0EtKK', 'Иван Петров', '+79001234568', 'Участок 1', 2, 'active'),
('treasurer@snt.ru', '$2y$10$vV6rjBUWpgOvFOxABD99g.tf91GZ4plSw01DrUXfG/aCTHSjpUeLu', 'Мария Сидорова', '+79001234569', 'Участок 2', 3, 'active'),
('secretary@snt.ru', '$2y$10$uvRpUTltbgeAXiWqrSTJne.YRipE16V8Pg8TlIaSY.qa1r9PijDHq', 'Елена Иванова', '+79001234570', 'Участок 3', 4, 'active'),
-- Основные тестовые пользователи (пароли: Test1234..Test1239)
('admin@snt-berezka.ru', '$2y$10$PSdVfA45Xr1QU4CvSl8o.eSAzoK4Ku2njORTlS43ggJ2boSKGpYcq', 'Администратор Системы', '+7-914-001-0001', 'Правление', 1, 'active'),
('chairman@snt-berezka.ru', '$2y$10$YVi.ZgitrL/xhGpcqZlcUO./aXQYf5e4YBC3EjAYAO0wAEGZO4To2', 'Иванов Пётр Сергеевич', '+7-914-001-0002', 'Участок №1', 2, 'active'),
('treasurer@snt-berezka.ru', '$2y$10$MadOrIP/NsPBi.SGMsw3LOT3YXg1u3r5BK1BFDHeJQav9OfGYx0i2', 'Петрова Галина Ивановна', '+7-914-001-0003', 'Участок №12', 3, 'active'),
('secretary@snt-berezka.ru', '$2y$10$s2R3wIaPJJwAL9YiMh1GIOwZru9HQUKCk9aRtqoYgi2b6dJcqbFyC', 'Сидоров Алексей Николаевич', '+7-914-001-0004', 'Участок №25', 4, 'active'),
('member@snt-berezka.ru', '$2y$10$XfV6z1rLLyXQWkEeDLRZJ.LxIMEpBQCq8rdiD3Ri2E0kKRN1PWoNe', 'Козлова Надежда Петровна', '+7-914-001-0005', 'Участок №47', 5, 'active'),
('guest@snt-berezka.ru', '$2y$10$zjI8yxZ2xYnQqtAuB.apKefV.7GHlztoG/DAoCOajVc/WEhpLOH32', 'Гостевой Пользователь', '+7-914-001-0006', '', 6, 'active');

-- Пароли тестовых пользователей:
--   @snt.ru (id 1-4):           password123
--   admin@snt-berezka.ru:       Test1234
--   chairman@snt-berezka.ru:    Test1235
--   treasurer@snt-berezka.ru:   Test1236
--   secretary@snt-berezka.ru:   Test1237
--   member@snt-berezka.ru:      Test1238
--   guest@snt-berezka.ru:       Test1239

-- ========================================
-- МИГРАЦИЯ: СИСТЕМА МНОЖЕСТВЕННЫХ ВЗНОСОВ
-- ========================================

-- Виды взносов (шаблоны)
CREATE TABLE IF NOT EXISTS fee_types (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL COMMENT 'Например: Членский взнос, Целевой: расчистка',
  year INT NOT NULL,
  amount DECIMAL(10, 2) NOT NULL COMMENT 'Требуемая сумма по умолчанию',
  plot_scope VARCHAR(10) NOT NULL DEFAULT 'all' COMMENT 'all — всем участкам, selected — только выбранным',
  description TEXT COMMENT 'Дополнительное описание',
  created_by INT COMMENT 'ID пользователя, создавшего вид взноса',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_year (year),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Виды взносов СНТ';

-- Платежи по взносам (по каждому участку)
CREATE TABLE IF NOT EXISTS fee_payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  fee_type_id INT NOT NULL,
  plot_number VARCHAR(10) NOT NULL COMMENT 'Номер участка',
  required_amount DECIMAL(10, 2) NOT NULL COMMENT 'Необходимая сумма',
  paid_amount DECIMAL(10, 2) DEFAULT 0 COMMENT 'Фактически оплачено',
  status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
  notes VARCHAR(255) COMMENT 'Примечание к платежу',
  paid_at TIMESTAMP NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fee_type_id) REFERENCES fee_types(id) ON DELETE CASCADE,
  UNIQUE KEY uq_fee_plot (fee_type_id, plot_number),
  INDEX idx_fee_type (fee_type_id),
  INDEX idx_plot (plot_number),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Учёт оплаты взносов по участкам';

-- ========================================
-- МИГРАЦИЯ: СОБРАНИЯ
-- ========================================

CREATE TABLE IF NOT EXISTS `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_date` date NOT NULL,
  `topic` varchar(300) NOT NULL,
  `location` varchar(200) NOT NULL DEFAULT 'Домик собраний',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Собрания СНТ';

-- ========================================
-- МИГРАЦИЯ: ОТЧЁТЫ О РАСХОДАХ
-- ========================================

CREATE TABLE IF NOT EXISTS `expense_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `fee_category` varchar(200) NOT NULL DEFAULT '',
  `document_url` varchar(500) NOT NULL,
  `document_name` varchar(300) NOT NULL,
  `document_size` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_by_name` varchar(200) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Отчёты о расходах';

-- ========================================
-- ОБРАЩЕНИЯ В ПРАВЛЕНИЕ (ТИКЕТЫ) — migration 003
-- ========================================

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('new','in_progress','answered','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Обращения членов СНТ в правление';

CREATE TABLE IF NOT EXISTS ticket_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Переписка по обращению';

-- ========================================
-- УВЕДОМЛЕНИЯ (события, напоминания о долге) — модель Notification
-- ========================================

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'info',
  title VARCHAR(200) NOT NULL,
  body TEXT,
  link VARCHAR(255) DEFAULT '',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Уведомления членам СНТ';
