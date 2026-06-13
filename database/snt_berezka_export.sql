SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;



DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `category` enum('sale','buy','service','info','wanted') NOT NULL DEFAULT 'info',
  `is_pinned` tinyint(1) DEFAULT 0 COMMENT 'Закреплённое объявление',
  `status` enum('draft','published','archived') DEFAULT 'published',
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  FULLTEXT KEY `idx_search` (`title`,`content`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Объявления и новости СНТ';


LOCK TABLES `announcements` WRITE;
INSERT INTO `announcements` VALUES (7,'Субботник 15 июня — просьба участвовать','Уважаемые садоводы! Приглашаем всех на субботник 15 июня в 10:00.',6,'info',0,'published',0,'2026-06-04 20:56:09','2026-06-04 20:56:09'),(10,'Продаю садовый инвентарь','Продаю лопату штыковую (300 руб), грабли (200 руб), тяпку (150 руб). Всё в рабочем состоянии. Участок №47, звоните.',9,'sale',0,'published',0,'2026-06-04 21:02:19','2026-06-04 21:02:19'),(11,'Объявление правления: сбор взносов до 30 июня','Уважаемые члены СНТ «Берёзка»! Напоминаем, что срок уплаты членских и целевых взносов за 2025 год — до 30 июня. Реквизиты и тарифы в разделе «Финансы». Правление.',6,'info',0,'published',0,'2026-06-04 21:02:22','2026-06-04 21:02:22'),(15,'Продаю газонокосилку BOSCH','Газонокосилка BOSCH Rotak 43, 2020 г. Состояние отличное. Использовалась два сезона. Причина продажи — переезд. Цена 8 500 руб., торг уместен. Участок №47, звонить вечером.',9,'sale',0,'published',0,'2026-06-09 10:32:21','2026-06-09 10:32:21'),(16,'Куплю старые садовые инструменты','Приобрету в любом состоянии: лопаты, грабли, тяпки, вёдра. Самовывоз. Участок №25, Сидоров Алексей. Расплачусь наличными.',8,'buy',0,'published',0,'2026-06-09 10:32:22','2026-06-09 10:32:22'),(18,'Ищу помощника по бухгалтерии','Казначей СНТ ищет волонтёра для помощи в подготовке годового финансового отчёта. Требуется 2-3 вечера. Будем благодарны за помощь. Участок №12.',7,'service',0,'published',0,'2026-06-09 10:32:36','2026-06-09 10:32:36'),(20,'Разыскивается пропавший кот Тимофей','Пропал рыжий кот, кличка Тимофей. Потерялся 5 июня в районе участков №30-40. Если нашли — пожалуйста, позвоните в правление или оставьте объявление на воротах. Большое спасибо!',5,'wanted',0,'published',0,'2026-06-09 10:32:39','2026-06-09 10:32:39'),(21,'Субботник 22 июня — ОБЯЗАТЕЛЬНО','Уважаемые садоводы! 22 июня в 9:00 общий субботник по территории СНТ. Просьба всем участникам принять участие. Инвентарь будет предоставлен. Председатель правления.',6,'info',0,'published',0,'2026-06-09 10:33:02','2026-06-09 10:33:02'),(22,'замена ктп','shfgh',5,'info',0,'published',0,'2026-06-10 20:01:58','2026-06-10 20:01:58');
UNLOCK TABLES;


DROP TABLE IF EXISTS `budget_expenses`;
CREATE TABLE `budget_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `document_url` varchar(500) DEFAULT '',
  `document_name` varchar(300) DEFAULT '',
  `document_size` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_by_name` varchar(200) DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_item` (`budget_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `budget_expenses` WRITE;
INSERT INTO `budget_expenses` VALUES (2,2,2300.00,'расчистка после циклона','','',0,6,'Иванов Пётр Сергеевич','2026-06-05 16:41:54');
UNLOCK TABLES;


DROP TABLE IF EXISTS `budget_items`;
CREATE TABLE `budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `planned_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `year` int(11) NOT NULL,
  `category` varchar(200) DEFAULT '',
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `budget_items` WRITE;
INSERT INTO `budget_items` VALUES (2,'расчистка снега','',56000.00,2026,'снег',6,'2026-06-05 16:41:29');
UNLOCK TABLES;


DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('устав','протокол','положение','смета','другое') DEFAULT 'другое',
  `file_path` varchar(255) NOT NULL COMMENT 'Путь к файлу на сервере',
  `file_size` int(11) DEFAULT NULL COMMENT 'Размер файла в байтах',
  `mime_type` varchar(50) DEFAULT NULL COMMENT 'application/pdf и т.д.',
  `uploaded_by` int(11) NOT NULL COMMENT 'ID пользователя, загрузившего',
  `is_public` tinyint(1) DEFAULT 1 COMMENT 'Видят все или только члены',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_uploaded` (`uploaded_by`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Документы: уставы, протоколы, сметы';


LOCK TABLES `documents` WRITE;
UNLOCK TABLES;


DROP TABLE IF EXISTS `expense_reports`;
CREATE TABLE `expense_reports` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;


LOCK TABLES `expense_reports` WRITE;
INSERT INTO `expense_reports` VALUES (1,2000.00,'починка фонарей','Ремонт и обслуживание','/uploads/reports/report_1780555892_4900.docx','Вопросы по смете расходов СНТ на 2025 год.docx',16870,6,'Иванов Пётр Сергеевич','2026-06-04 17:51:32');
UNLOCK TABLES;


DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_type_id` int(11) NOT NULL,
  `plot_number` varchar(10) NOT NULL COMMENT 'Номер участка',
  `required_amount` decimal(10,2) NOT NULL COMMENT 'Необходимая сумма',
  `paid_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Фактически оплачено',
  `status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `notes` varchar(255) DEFAULT NULL COMMENT 'Примечание к платежу',
  `paid_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fee_plot` (`fee_type_id`,`plot_number`),
  KEY `idx_fee_type` (`fee_type_id`),
  KEY `idx_plot` (`plot_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=706 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Учёт оплаты взносов по участкам';


LOCK TABLES `fee_payments` WRITE;
INSERT INTO `fee_payments` VALUES (216,18,'1',17795.00,0.00,'unpaid','',NULL,'2026-06-12 09:08:30','2026-06-11 22:14:48'),(217,18,'4',14253.00,14253.00,'paid','','2026-06-11 13:15:07','2026-06-11 22:15:07','2026-06-11 22:14:48'),(218,18,'5',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:48','2026-06-11 22:14:48'),(219,18,'8',13394.00,13394.00,'paid','','2026-06-11 13:15:44','2026-06-11 22:15:44','2026-06-11 22:14:48'),(220,18,'10',9529.00,9529.00,'paid','','2026-06-11 13:15:12','2026-06-11 22:15:12','2026-06-11 22:14:48'),(221,18,'11',14723.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:48','2026-06-11 22:14:48'),(222,18,'12',9459.00,0.00,'unpaid','',NULL,'2026-06-13 02:21:46','2026-06-11 22:14:48'),(223,18,'14',0.00,0.00,'paid',NULL,'2026-06-11 13:14:48','2026-06-11 22:14:48','2026-06-11 22:14:48'),(224,18,'15',14847.00,14847.00,'paid','','2026-06-11 13:15:15','2026-06-11 22:15:15','2026-06-11 22:14:49'),(225,18,'16',117.00,117.00,'paid','','2026-06-11 13:15:17','2026-06-11 22:15:17','2026-06-11 22:14:49'),(226,18,'17',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(227,18,'18',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(228,18,'22',130.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(229,18,'25',1449.00,1449.00,'paid','','2026-06-12 17:21:35','2026-06-13 02:21:35','2026-06-11 22:14:49'),(230,18,'26',112.00,112.00,'paid','','2026-06-12 17:21:39','2026-06-13 02:21:39','2026-06-11 22:14:49'),(231,18,'27',136.00,136.00,'paid','','2026-06-12 17:21:43','2026-06-13 02:21:43','2026-06-11 22:14:49'),(232,18,'28',14424.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(233,18,'31',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(234,18,'36',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(235,18,'37',132.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(236,18,'38',143.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(237,18,'39',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(238,18,'42',264.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(239,18,'46',789.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(240,18,'47',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(241,18,'48',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(242,18,'49',409.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:49','2026-06-11 22:14:49'),(243,18,'50',195.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:50','2026-06-11 22:14:50'),(244,18,'52',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:50','2026-06-11 22:14:50'),(245,18,'54',112.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:50','2026-06-11 22:14:50'),(246,18,'55',1744.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:50','2026-06-11 22:14:50'),(247,18,'58',111.00,0.00,'unpaid',NULL,NULL,'2026-06-11 22:14:50','2026-06-11 22:14:50'),(574,7,'47',5000.00,0.00,'unpaid',NULL,NULL,'2026-06-11 23:23:45','2026-06-11 23:23:44'),(604,33,'1',6700.00,6700.00,'paid','','2026-06-12 00:31:06','2026-06-12 09:31:06','2026-06-12 09:27:54'),(605,33,'2',6700.00,6700.00,'paid','','2026-06-12 00:31:11','2026-06-12 09:31:11','2026-06-12 09:27:54'),(606,33,'3',6700.00,6700.00,'paid','','2026-06-12 00:31:12','2026-06-12 09:31:12','2026-06-12 09:27:54'),(607,33,'4',6700.00,6700.00,'paid','','2026-06-12 00:31:13','2026-06-12 09:31:13','2026-06-12 09:27:54'),(608,33,'5',6700.00,6700.00,'paid','','2026-06-12 17:21:08','2026-06-13 02:21:08','2026-06-12 09:27:54'),(609,33,'6',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:54','2026-06-12 09:27:54'),(610,33,'7',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:54','2026-06-12 09:27:54'),(611,33,'8',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:54','2026-06-12 09:27:54'),(612,33,'9',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:54','2026-06-12 09:27:54'),(613,33,'10',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(614,33,'11',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(615,33,'13',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(616,33,'14',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(617,33,'15',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(618,33,'16',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(619,33,'17',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(620,33,'18',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(621,33,'19',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(622,33,'20',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(623,33,'21',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(624,33,'23',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(625,33,'24',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(626,33,'25',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(627,33,'26',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(628,33,'27',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(629,33,'28',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(630,33,'29',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(631,33,'30',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(632,33,'32',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(633,33,'33',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(634,33,'34',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(635,33,'35',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(636,33,'36',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(637,33,'37',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(638,33,'38',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(639,33,'39',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(640,33,'40',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(641,33,'41',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(642,33,'42',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(643,33,'43',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:55','2026-06-12 09:27:55'),(644,33,'44',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(645,33,'45',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(646,33,'46',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(647,33,'47',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(648,33,'48',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(649,33,'49',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(650,33,'50',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(651,33,'51',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(652,33,'52',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(653,33,'53',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(654,33,'54',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(655,33,'55',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(656,33,'56',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(657,33,'57',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(658,33,'58',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(659,33,'59',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(660,33,'60',6700.00,0.00,'unpaid',NULL,NULL,'2026-06-12 09:27:56','2026-06-12 09:27:56'),(661,33,'61',6700.00,6700.00,'paid','','2026-06-12 15:53:34','2026-06-13 00:53:34','2026-06-12 09:27:56'),(663,34,'1',4000.00,4000.00,'paid','','2026-06-12 00:31:07','2026-06-12 09:31:07','2026-06-12 09:31:07'),(664,35,'1',15000.00,15000.00,'paid','','2026-06-12 00:31:08','2026-06-12 09:31:08','2026-06-12 09:31:08'),(665,36,'1',4000.00,4000.00,'paid','','2026-06-12 13:32:40','2026-06-12 22:32:40','2026-06-12 09:31:08'),(666,37,'1',2000.00,0.00,'unpaid','',NULL,'2026-06-12 22:32:34','2026-06-12 09:31:09'),(670,35,'3',15000.00,15000.00,'paid','','2026-06-12 00:31:16','2026-06-12 09:31:16','2026-06-12 09:31:16'),(671,35,'2',15000.00,15000.00,'paid','','2026-06-12 00:31:16','2026-06-12 09:31:16','2026-06-12 09:31:16'),(672,35,'4',15000.00,15000.00,'paid','','2026-06-12 00:31:17','2026-06-12 09:31:17','2026-06-12 09:31:17'),(673,35,'5',15000.00,15000.00,'paid','','2026-06-12 00:31:18','2026-06-12 09:31:18','2026-06-12 09:31:18'),(674,37,'2',2000.00,2000.00,'paid','','2026-06-12 00:31:19','2026-06-12 09:31:19','2026-06-12 09:31:19'),(675,37,'3',2000.00,2000.00,'paid','','2026-06-12 00:31:20','2026-06-12 09:31:20','2026-06-12 09:31:20'),(676,37,'4',2000.00,2000.00,'paid','','2026-06-12 00:31:20','2026-06-12 09:31:20','2026-06-12 09:31:20'),(677,37,'5',2000.00,2000.00,'paid','','2026-06-12 00:31:21','2026-06-12 09:31:21','2026-06-12 09:31:21'),(680,36,'2',4000.00,4000.00,'paid','','2026-06-12 13:32:38','2026-06-12 22:32:38','2026-06-12 22:32:38'),(682,36,'3',4000.00,4000.00,'paid','','2026-06-12 13:32:41','2026-06-12 22:32:41','2026-06-12 22:32:41'),(684,34,'61',4000.00,4000.00,'paid','','2026-06-12 15:53:36','2026-06-13 00:53:36','2026-06-13 00:53:36'),(685,35,'61',15000.00,15000.00,'paid','','2026-06-12 15:53:37','2026-06-13 00:53:37','2026-06-13 00:53:37'),(686,36,'61',4000.00,4000.00,'paid','','2026-06-12 15:53:38','2026-06-13 00:53:38','2026-06-13 00:53:38'),(687,37,'61',2000.00,2000.00,'paid','','2026-06-12 15:53:39','2026-06-13 00:53:39','2026-06-13 00:53:39'),(689,36,'5',4000.00,4000.00,'paid','','2026-06-12 17:21:10','2026-06-13 02:21:10','2026-06-13 02:21:10'),(690,34,'5',4000.00,4000.00,'paid','','2026-06-12 17:21:11','2026-06-13 02:21:11','2026-06-13 02:21:11');
UNLOCK TABLES;


DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL COMMENT 'Например: Членский взнос, Целевой: расчистка',
  `year` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Требуемая сумма по умолчанию',
  `plot_scope` varchar(10) NOT NULL DEFAULT 'all' COMMENT 'all — всем участкам, selected — только выбранным',
  `description` text DEFAULT NULL COMMENT 'Дополнительное описание',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID пользователя, создавшего вид взноса',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allocation_type` enum('all','all_except_board','specific') DEFAULT 'all',
  `allocation_plots` varchar(500) DEFAULT NULL COMMENT 'JSON ',
  PRIMARY KEY (`id`),
  KEY `idx_year` (`year`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Виды взносов СНТ';


LOCK TABLES `fee_types` WRITE;
INSERT INTO `fee_types` VALUES (7,'Членский взнос 2025',2025,5000.00,'all','Ежегодный членский взнос',NULL,'2026-06-04 06:26:55','all',NULL),(8,'Целевой взнос на воду',2025,3000.00,'all','Ремонт водопровода',NULL,'2026-06-04 06:26:55','all',NULL),(18,'Свет Апрель 2026',2026,0.00,'all','Счёт за электроэнергию',5,'2026-06-11 22:14:48','all',NULL),(33,'сленский',2026,6700.00,'selected','',5,'2026-06-12 09:27:54','all',NULL),(34,'зимнее содержание',2026,4000.00,'all','',5,'2026-06-12 09:29:32','all',NULL),(35,'ремонт ктп',2026,15000.00,'all','',5,'2026-06-12 09:29:47','all',NULL),(36,'дороги',2026,4000.00,'all','',5,'2026-06-12 09:30:14','all',NULL),(37,'резерв 10%',2026,2000.00,'all','не подлежит согласованию',5,'2026-06-12 09:30:35','all',NULL);
UNLOCK TABLES;


DROP TABLE IF EXISTS `finances`;
CREATE TABLE `finances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense','fee','payment','penalty','refund') NOT NULL DEFAULT 'fee',
  `fee_type_id` int(11) DEFAULT NULL COMMENT 'Категория взноса, из которой трата',
  `amount` decimal(10,2) NOT NULL COMMENT 'Сумма в рублях',
  `description` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL COMMENT 'Номер квитанции',
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_fee_type_id` (`fee_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Финансовые операции: взносы, платежи';


LOCK TABLES `finances` WRITE;
INSERT INTO `finances` VALUES (6,5,'expense',14,12000.00,'Расход из резерва (описание утрачено из-за ошибки сохранения, исправлено 11.06.2026)',NULL,'paid',NULL,NULL,'2026-06-10 19:49:12','2026-06-10 19:56:35','expenses/20260610_214912_TZ_Web_Design.docx','TZ_Web_Design.docx'),(7,6,'expense',NULL,777.00,'SWARM-TEST expense A',NULL,'paid',NULL,NULL,'2026-06-10 19:59:17','2026-06-10 19:59:17',NULL,NULL),(8,6,'expense',14,1234.50,'SWARM-TEST expense B',NULL,'paid',NULL,NULL,'2026-06-10 19:59:23','2026-06-10 19:59:23',NULL,NULL),(9,5,'expense',14,2370.00,'шлагбаум',NULL,'paid',NULL,NULL,'2026-06-11 22:20:01','2026-06-11 22:20:01','expenses/20260612_002001_TZ_Web_Design.docx','TZ_Web_Design.docx'),(10,5,'expense',19,10000.00,'канцелярия',NULL,'paid',NULL,NULL,'2026-06-12 04:58:20','2026-06-12 04:58:20',NULL,NULL);
UNLOCK TABLES;


DROP TABLE IF EXISTS `meetings`;
CREATE TABLE `meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meeting_date` date NOT NULL,
  `topic` varchar(300) NOT NULL,
  `location` varchar(200) NOT NULL DEFAULT 'Домик собраний',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `meetings` WRITE;
INSERT INTO `meetings` VALUES (1,'2026-06-15','Годовое общее собрание','Домик собраний',NULL,NULL,'2026-06-05 15:58:17',0),(2,'2026-09-20','Осеннее собрание','Домик собраний',NULL,NULL,'2026-06-05 15:58:17',0),(4,'2026-06-20','уверждение совета и бюджета','',NULL,NULL,'2026-06-12 09:20:45',0);
UNLOCK TABLES;


DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `notifications` WRITE;
INSERT INTO `notifications` VALUES (1,1,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(2,2,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(3,3,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(4,4,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(5,6,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',1,'2026-06-12 15:51:50'),(6,7,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(7,8,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(8,9,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',1,'2026-06-12 15:51:50'),(9,10,'reminder','Напоминание об оплате взносов','Пожалуйста, проверьте задолженность по взносам за 2026 год в разделе «Взносы».','/finances',0,'2026-06-12 15:51:50'),(12,1,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(13,2,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(14,3,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(15,4,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(16,5,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',1,'2026-06-12 20:45:07'),(17,7,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(18,8,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(19,9,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',1,'2026-06-12 20:45:07'),(20,10,'meeting','Назначено событие','Тема: ТЕСТ Общее собрание членов СНТ. Дата: 15.07.2026.','/feed?tab=events',0,'2026-06-12 20:45:07'),(21,1,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(22,2,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(23,3,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(24,4,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(25,5,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',1,'2026-06-12 20:45:08'),(26,7,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(27,8,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08'),(28,9,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',1,'2026-06-12 20:45:08'),(29,10,'vote','Новое голосование','ТЕСТ Утвердить смету на 2026 год?','/feed?tab=votes',0,'2026-06-12 20:45:08');
UNLOCK TABLES;


DROP TABLE IF EXISTS `registrations`;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL COMMENT 'Причина присоединения',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'ID админа, одобрившего заявку',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Заявки на регистрацию в СНТ';


LOCK TABLES `registrations` WRITE;
INSERT INTO `registrations` VALUES (1,'Zheezh@yandex.ru','Елена','+79625813213','Участок No28','','approved',6,'2026-06-09 10:53:27','2026-06-04 06:06:20'),(2,'Zheezh@yandex.ru','Лобкова Наталья Викторовна','','13','','approved',6,'2026-06-13 02:26:21','2026-06-13 02:26:07');
UNLOCK TABLES;


DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'админ, председатель, казначей, секретарь, член, гость',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Роли и должности в СНТ';


LOCK TABLES `roles` WRITE;
INSERT INTO `roles` VALUES (1,'админ','Полный доступ к системе','2026-06-04 06:00:06'),(2,'председатель','Возглавляет правление','2026-06-04 06:00:06'),(3,'казначей','Управляет финансами','2026-06-04 06:00:06'),(4,'секретарь','Ведёт протоколы и документы','2026-06-04 06:00:06'),(5,'член','Обычный член СНТ','2026-06-04 06:00:06'),(6,'гость','Неавторизованный пользователь','2026-06-04 06:00:06');
UNLOCK TABLES;


DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL COMMENT 'JWT или сессионный токен',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Активные сессии пользователей';


LOCK TABLES `sessions` WRITE;
INSERT INTO `sessions` VALUES (2,6,'09c835dffc8019f5c8cedc03584aecc87002515cd69f5b919156582735bc4d66','::1',NULL,'2026-06-10 21:14:58','2026-06-04 06:14:58'),(3,7,'f4eca1a2421c3ec80ad7cf4373ae1bfaf9a4a91c3ee32d3f3a9fa8a079eca83b','::1',NULL,'2026-06-10 21:15:03','2026-06-04 06:15:03'),(4,9,'49d4ef0ac59a01d56fcd765a705c1c0e2264d84c2e003e5f52eefb5350913374','::1',NULL,'2026-06-10 21:15:10','2026-06-04 06:15:10'),(5,8,'71b5a99799af80aed7b4d9770730c5a34c4635fc53ce157b1d55082187963957','::1',NULL,'2026-06-10 21:15:17','2026-06-04 06:15:17'),(6,6,'8bacf555fd9f7063cb0b283780a125b893ab593773ecfa965eae80dcc070599c','::1',NULL,'2026-06-10 21:15:58','2026-06-04 06:15:58'),(7,6,'687d6c9ecf2fd26f64ce8a7a0a17e7a13332ea2a15fa0be0ab5ce5220a63c144','::1',NULL,'2026-06-10 21:19:03','2026-06-04 06:19:03'),(8,7,'69e9bda7187e86af4bdaca9ec7d2eee87c1ff88a9c04db037dab3e6bcaeca7bd','::1',NULL,'2026-06-10 21:19:04','2026-06-04 06:19:04'),(9,9,'b82277c43d270e5e6df3ec022f4f49134ed319f740084bec66a69645fb984e84','::1',NULL,'2026-06-10 21:19:04','2026-06-04 06:19:04'),(10,8,'fedca43ff46af14189d68afb317baab7fa43995fd67449f972dd9b9e183cb70d','::1',NULL,'2026-06-10 21:19:04','2026-06-04 06:19:04'),(11,6,'7cde505f35b20e1c6839165d671c2bc4864f979c52e0e590dd5cf156ce1560eb','::1',NULL,'2026-06-10 21:19:53','2026-06-04 06:19:53'),(12,9,'10c162d0ab8a5cb25b4a29e6622c0e733c792fffe95b5ebb079ac6722376d304','::1',NULL,'2026-06-10 21:19:54','2026-06-04 06:19:54'),(13,6,'c6cdf46d08b52b555720c4f3319550d1f03d9022dd1424a89e8b9ac8a927ac6a','::1',NULL,'2026-06-10 21:22:13','2026-06-04 06:22:13'),(14,8,'670772389edcaccf2edce532946fe9811129e22ad8a985980448b70a055ef73e','::1',NULL,'2026-06-10 21:22:14','2026-06-04 06:22:14'),(15,9,'c3cb3b084e8960eb42c6c0cfe82b2ff785a5a3558e93ff7f553382b61dda2786','::1',NULL,'2026-06-10 21:22:14','2026-06-04 06:22:14'),(16,6,'dbfb32346bd6f508438e8e5877da1a3bf6e97fabf74702f7d99ebdf320e1b507','::1',NULL,'2026-06-10 21:24:31','2026-06-04 06:24:31'),(17,6,'462342e94e10251b51edabc0db2487abcc101fd2338f02b6d6bba3255532e450','::1',NULL,'2026-06-10 21:25:23','2026-06-04 06:25:23'),(18,6,'bbba482b2e2663b6aa8c1c8d8225b87f88b41c733243b3c598450a67403825d7','::1',NULL,'2026-06-10 21:25:39','2026-06-04 06:25:39'),(19,6,'57e3819c254d154d6e9065cad0d3aa378c6d4eb885c2a2d386a2318e743192b4','::1',NULL,'2026-06-10 21:28:12','2026-06-04 06:28:12'),(20,7,'4092ff27c6eef26806125619b4493f1fd4bfdb740a61bbe4848618042f39cf28','::1',NULL,'2026-06-10 21:28:13','2026-06-04 06:28:13'),(21,9,'0e0d88f95035f7a84f08717a2f90518ba02844e9933843e3d589f7b9e8431d91','::1',NULL,'2026-06-10 21:28:13','2026-06-04 06:28:13'),(22,8,'33bae763f504617a55260852ae7925523cceb1a928ec64e90b8f85448912fb55','::1',NULL,'2026-06-10 21:28:13','2026-06-04 06:28:13'),(23,6,'62fc762ca228abd1875a3d3197bc1c7bddaf5d8c3bf3241ef936b3ab4bda159b','::1',NULL,'2026-06-10 21:28:34','2026-06-04 06:28:34'),(24,9,'6b9df5cc873ef312fc1d86be6bd3673b0650250a2846b130cab3867ec9e6d23b','::1',NULL,'2026-06-10 21:28:34','2026-06-04 06:28:34'),(25,8,'0fa58ff38962b73f7d8eeb829105f0ecfb87dc8497a23881bf060d470d3db883','::1',NULL,'2026-06-10 21:28:34','2026-06-04 06:28:34'),(26,6,'78752d4ead08926455da1cb5e1b9dd4990599a4e354e0a06e77acdfaef12aaf2','::1',NULL,'2026-06-10 21:29:01','2026-06-04 06:29:01'),(29,6,'ca0ae09df5156250ecaba4ae78f4c0299edeeb281b84fa9b3f102ace8d60e324','::1',NULL,'2026-06-10 21:57:14','2026-06-04 06:57:14'),(33,6,'26c771a3ca6315b55d1b3bc75a17a275f7b2a9a7b9c859f92d344334d3d27e0a','::1',NULL,'2026-06-10 22:44:05','2026-06-04 07:44:05'),(34,9,'7670bfc8de49f0e40f3eb09f7c0d84d1839e82e4263ceeaf579fa12f4fcda466','::1',NULL,'2026-06-11 11:55:29','2026-06-04 20:55:29'),(35,6,'06ce42c67d7181ef7a4a63f0934012c5ed1f653a80b733609c5ba70304dcda53','::1',NULL,'2026-06-11 11:55:31','2026-06-04 20:55:31'),(36,6,'463ef48682c4e732bfae5eed14b7a1ec201a5d095fa24283cbb39fe826861be9','::1',NULL,'2026-06-11 11:59:42','2026-06-04 20:59:42'),(37,9,'c322548b9e038b1d9e59c5f1fbb9f86332c68f3b7474261e94af32d4671d0860','::1',NULL,'2026-06-11 11:59:45','2026-06-04 20:59:45'),(38,9,'e713ddc6e196c1accfb733c3ac535217ca6952116e07432a6877008057183bc1','::1',NULL,'2026-06-11 12:00:33','2026-06-04 21:00:33'),(39,9,'300914bbbdffa1fb13a2584501cbc83b6c192e50a7eaad12d833f95c6211763a','::1',NULL,'2026-06-11 12:02:12','2026-06-04 21:02:12'),(40,6,'24986ab48eed0c93bc2078783d97c371f79e78952a57e977817279be5dccb6f9','::1',NULL,'2026-06-11 12:02:14','2026-06-04 21:02:14'),(41,9,'5aa19147213db6ec261dafb494b452c091e69197cee6cd7fad090b5779a4b8a2','::1',NULL,'2026-06-11 12:03:12','2026-06-04 21:03:12'),(42,6,'8d7dff57e8e18f1fd9d46351b231e30edd0cfbd0042503ca1e6199b8f6ec866b','::1',NULL,'2026-06-11 12:03:58','2026-06-04 21:03:58'),(43,9,'89c7eb79a5d803a0f0a98089c54c528e4e03e081fdd19ac720011b02b269a99f','::1',NULL,'2026-06-11 12:05:18','2026-06-04 21:05:18'),(44,6,'1099f6c46cbc8e6e453f231eba7e531d79c60c913d003585727a8a3561a927fc','::1',NULL,'2026-06-11 12:09:03','2026-06-04 21:09:03'),(45,9,'09abc39fdf007a3d6844e8a120a29f9aa6afb5d5926902b2663beb6e7ba0262d','::1',NULL,'2026-06-11 12:10:03','2026-06-04 21:10:03'),(46,9,'76a62c71ac259e95e43b3d8c31c8e89757177398bf6b618dc8ed497cf8539c13','::1',NULL,'2026-06-11 12:10:47','2026-06-04 21:10:47'),(47,9,'5018a717a730c94755e7987003a6645d1563fe0e0b6be7dd98287a3aa807274a','::1',NULL,'2026-06-11 12:11:15','2026-06-04 21:11:15'),(48,9,'1ed15703489f59774c54565e9c9d1804e9103a17b58f43c2f320069327d6ea27','::1',NULL,'2026-06-11 12:12:02','2026-06-04 21:12:02'),(49,6,'f9fc5e45a7c98c9e696673967093bab95acd62c2706c967c62e30ef2c2a17f06','::1',NULL,'2026-06-11 12:30:05','2026-06-04 21:30:05'),(50,6,'6c7374eacf20df05942a24faa6bfba29ad0d34c78fa22a1cdffa64143081ce53','::1',NULL,'2026-06-11 19:45:05','2026-06-05 04:45:05'),(53,6,'f7a0a18359e3ebc5c58b56c03bd4f312067843829a8f24f20717452da6da1990','::1',NULL,'2026-06-11 20:44:31','2026-06-05 05:44:31'),(54,5,'7f4a9cdc659eaa76a3b39d1cd90d3d2fab65a95ed3ab9ab1752a18e4b55b188f','::1',NULL,'2026-06-16 01:54:34','2026-06-09 10:54:34'),(55,6,'5ffdcb63ae2b21ef7a8250ab0c547d00ffbf0cc86bc23be1a345f878bb646ead','::1',NULL,'2026-06-16 01:54:34','2026-06-09 10:54:34'),(56,5,'7c5daa359b8b259d106d06674864d12bf2d947687c953ba42b80e1dd44a0ccbf','::1',NULL,'2026-06-16 01:54:47','2026-06-09 10:54:47'),(57,6,'c9c361b825c66b5f942da5b5a38db731b8c26eb38920ddd7fd2e6564c13ae2df','::1',NULL,'2026-06-16 01:54:48','2026-06-09 10:54:48'),(58,5,'aa5eb376e8e1f48da7375cb2936a34a6812f8d16093316443c88ec735bf02c70','::1',NULL,'2026-06-16 01:54:58','2026-06-09 10:54:58'),(59,6,'edf2017491f098fddc61ad3c94427d0ad667f64121f7479f080f163a64e8c0b0','::1',NULL,'2026-06-16 01:54:59','2026-06-09 10:54:59'),(60,6,'077ff78b9bb57f75794ec1897e30bf01b6ab246a9eab7337792b400be44764de','::1',NULL,'2026-06-16 01:55:11','2026-06-09 10:55:11'),(61,6,'1ec77943250d002c5769c9f2540f860763e2cdde1ceb4d931c07f6a530af3fa6','::1',NULL,'2026-06-16 01:55:19','2026-06-09 10:55:19'),(62,5,'072af09be6a3f5e36418a189218b8c15f3377e2e0b5a86f592ca8f6fb40e1752','::1',NULL,'2026-06-16 01:56:26','2026-06-09 10:56:26'),(63,6,'e2755821b0d8c1d994a04e91094a454dee4953dc736b3cfd3a70c85346c585d5','::1',NULL,'2026-06-16 20:17:43','2026-06-10 05:17:43'),(64,6,'17790506478877db321043fd517299123897d7673786c08175e8180cae17e9f7','::1',NULL,'2026-06-16 20:19:25','2026-06-10 05:19:25'),(65,6,'f7c4e6644ebed828a85bffe7e0eaaf268210aa0f84a030d680724912abaeaa61','::1',NULL,'2026-06-16 20:21:33','2026-06-10 05:21:33'),(66,7,'64b67cd6809a385ff1e20ea4ce4305c0db516f9518dea18286649d7827b2b21b','::1',NULL,'2026-06-18 13:47:58','2026-06-11 22:47:58'),(67,7,'948218e8d976ea682711fa4ea8bd593097e8d4bba577e550a9871eca21a0c719','::1',NULL,'2026-06-18 13:48:14','2026-06-11 22:48:14'),(68,7,'69312064282614bfac3b5427344b723eb33c4d6576ebb02e34ad293e20e699af','::1',NULL,'2026-06-18 13:48:20','2026-06-11 22:48:20'),(69,9,'59563ec05123947fed09a2088527baacc7db229f03c64f7a7f1c89aba744cae2','::1',NULL,'2026-06-18 13:49:03','2026-06-11 22:49:03'),(72,7,'433d946c8624192fb33e8ca89100086b612eb942ba831fa0820f1db05e830939','::1',NULL,'2026-06-18 13:51:27','2026-06-11 22:51:27'),(74,5,'01c2f3129ab4812aa495a2c588c166d0366adb3859d31daa343da03703989248','::1',NULL,'2026-06-18 13:54:41','2026-06-11 22:54:41'),(75,5,'70b6c0fbe23f3c1d217a8de30689a2a10cd0521d02d5e3d1bc094868570623e5','::1',NULL,'2026-06-18 13:55:35','2026-06-11 22:55:35'),(76,9,'12ba7a122c944ec3390641f952890fa87e54fc4e6c0de9fc9180434eb4abdc8d','::1',NULL,'2026-06-18 13:55:53','2026-06-11 22:55:53'),(77,7,'1015aa9dbd627ef1e7cd8d7b21844b687c603ad8953a26665bca5c744eb3a657','::1',NULL,'2026-06-18 13:57:33','2026-06-11 22:57:33'),(78,9,'8165cbb8cb90607b7db610cf1be8758534ec142c067dc7fc1f44a45533cc3939','::1',NULL,'2026-06-18 13:57:36','2026-06-11 22:57:36'),(79,7,'d74a203e887daff96eb3e479216dabe88c78719befa70e2c9b8f75f694d28e8a','::1',NULL,'2026-06-18 13:57:37','2026-06-11 22:57:37'),(80,5,'f24a10474f8d9abfe6fd68ada58e27fdcc596518519442757d4191dfc9619cf4','::1',NULL,'2026-06-18 13:58:06','2026-06-11 22:58:06'),(81,5,'3b4cbe57b98a7b2a49463435d7d4867b909a6f4ddda81181ae072092f7158b13','::1',NULL,'2026-06-18 14:02:19','2026-06-11 23:02:19'),(82,5,'0c7f03fb5428b19f162d963aa308d378bb9b2d8f02d23671070df391cea3eeca','::1',NULL,'2026-06-18 14:02:48','2026-06-11 23:02:48'),(83,6,'4fd7813420a452d5303d0608c7029b007e02b1667a956ef5fc8c5f1cb87723ea','::1',NULL,'2026-06-18 14:03:05','2026-06-11 23:03:05'),(85,5,'920a20983dd4c2576ed9a526b6591446d93f0cee0a74a833868308b536db37de','::1',NULL,'2026-06-18 14:03:30','2026-06-11 23:03:30'),(86,6,'569abb4bb1ed6d7eae0d394f9f753f504a787f936e43fd4d35e22063c33f2a83','::1',NULL,'2026-06-18 14:03:30','2026-06-11 23:03:30'),(87,5,'a260c8bc9d5c63aba6c2fc04240b7a27c310fed08e9491333d0d124713a4de37','::1',NULL,'2026-06-18 14:04:25','2026-06-11 23:04:25'),(88,7,'d7bcdf9868fdd1167a07816dbc43ab6a6983fd62ca1ca8fc56f8af49e4c9a39c','::1',NULL,'2026-06-18 14:04:25','2026-06-11 23:04:25'),(89,6,'1e3e107cccc72ecd7608873dd5a096ff84cd98799596f2b4f62a1912afa638e7','::1',NULL,'2026-06-18 14:06:19','2026-06-11 23:06:19'),(90,9,'f6d9453f613ae8b8e4ee7ccde55a067f4a1e63cb61b9dcadaf4095e21462b0da','::1',NULL,'2026-06-18 14:06:44','2026-06-11 23:06:44'),(91,6,'b024b7e40591e4f68678733711e5401426decd7b5b8e250106efa9ea7e1bfeeb','::1',NULL,'2026-06-18 14:06:53','2026-06-11 23:06:53'),(92,6,'4473bad03dc9089ccf7fbe0f7013f5a66fab5d7b0bae4f0f6050fc1fd9de3a71','::1',NULL,'2026-06-18 14:07:09','2026-06-11 23:07:09'),(94,7,'962ca3050d885e4053a9821482fe7c1c3d0693a8639b06dbe8a1dd962b3985a8','::1',NULL,'2026-06-18 14:08:49','2026-06-11 23:08:49'),(95,7,'78f0d3b4fa78f7ba2846293f14ba475916bd35c3c71452dd230d5a06088c494f','::1',NULL,'2026-06-18 14:13:55','2026-06-11 23:13:55'),(96,5,'47cc4c2ead711b4016d2fccdc7888c158d16ba711f9a9240abf734ae68964b05','::1',NULL,'2026-06-18 14:13:56','2026-06-11 23:13:56'),(97,9,'432f92854f44277cebd0cf76d3798fe79e8cbe4107bfe67490220b8fbb9f53d0','::1',NULL,'2026-06-18 14:13:58','2026-06-11 23:13:58'),(98,6,'be6555102d46004d743be289b8a8d7135047fa48260c7fe19f7c26ca6184f19b','::1',NULL,'2026-06-18 14:15:07','2026-06-11 23:15:07'),(99,7,'d1dad72c911ace470fdddfb88c6acbf9319c074fc2ca7aec733256426288a2c7','::1',NULL,'2026-06-18 14:15:36','2026-06-11 23:15:36'),(100,7,'ce38b8c8d24ef717ee306424fd5c8329dffec107c62d215d6057a49f3c3491bd','::1',NULL,'2026-06-18 14:15:54','2026-06-11 23:15:54'),(101,7,'1e0ea316397499a1c1d9d8b32040d8ff1375c39745f0debfe07622d8f2b95a6d','::1',NULL,'2026-06-18 14:16:16','2026-06-11 23:16:16'),(102,7,'04107d22af95d8bd76e5312e2abc0c7ca11a8ed573753f0b821f61806cd919e6','::1',NULL,'2026-06-18 14:16:59','2026-06-11 23:16:59'),(103,7,'00e185c4bc5d5effd1b194db7c6e73e135bbced4abca1dd6da6b5269c2301e1f','::1',NULL,'2026-06-18 14:17:02','2026-06-11 23:17:02'),(104,7,'59cff4771fd5adbeda906a0534679a220b2957dc21a3b773b4e85e5276ece318','::1',NULL,'2026-06-18 14:17:05','2026-06-11 23:17:05'),(105,7,'c9ccd0425e17808a21094840d79a84b457a599166df82988e11807fa324ba91d','::1',NULL,'2026-06-18 14:17:08','2026-06-11 23:17:08'),(106,5,'a9c6f5d8eecea803dce39fe09b173740a48f87ea9972fed71274272bc8547ea6','::1',NULL,'2026-06-18 14:17:23','2026-06-11 23:17:23'),(107,7,'d392505c2e2e180ef1eac52046fc47617de7526f66113606f2d9d73ffbfd79ba','::1',NULL,'2026-06-18 14:17:24','2026-06-11 23:17:24'),(108,5,'71372766eb961da6d9a67dfafbc395560ca0ec3aba6221fd0306b42cacabdfad','::1',NULL,'2026-06-18 14:18:51','2026-06-11 23:18:51'),(109,5,'6f8779a166381a719aef95dcc11eb4afaeaa3c86ccc8c01c385a63e6a3439169','::1',NULL,'2026-06-18 14:18:51','2026-06-11 23:18:51'),(110,5,'5e9195595ef275aaa71181a56d40d90a20523536fdd8897ef938ba23915a9b3b','127.0.0.1',NULL,'2026-06-18 14:18:52','2026-06-11 23:18:52'),(111,5,'d05df6f98300dd2f316653774abd85bc11c39dab41b6efa83521151c9300e558','::1',NULL,'2026-06-18 14:18:56','2026-06-11 23:18:56'),(112,5,'c7c5d1bc177bac07b7568bc1d3bca20b00deb54a53e44a3de30a02ed4a095016','::1',NULL,'2026-06-18 14:19:03','2026-06-11 23:19:03'),(113,5,'68d7945e5443e8fd82225e597a6e500192a37c1f9dc457e3819d6f44944fe4e8','::1',NULL,'2026-06-18 14:19:33','2026-06-11 23:19:33'),(114,5,'e4ab854196a358a9ef2919ae7826f3796c75ba29260a2b54dba2dcb789f8f86b','::1',NULL,'2026-06-18 14:19:54','2026-06-11 23:19:54'),(115,5,'d90eed5cf7e972917f72414e786aae789c46a8011817d0433e0a10a22d8123f7','::1',NULL,'2026-06-18 14:20:06','2026-06-11 23:20:06'),(116,5,'e00fa75637840da209629378168c4c71c36024eb008d980de7ce52fc11030e3f','::1',NULL,'2026-06-18 14:20:18','2026-06-11 23:20:18'),(117,5,'1ec7d2300be137f8dc01f6000d251cfd29d0d8f6edfe276aa8a9269192c4edd9','::1',NULL,'2026-06-18 14:21:06','2026-06-11 23:21:06'),(118,5,'5b7d7795321e120bb0fb7b2074e8f27d3bb7e2663f42b1b97d8bd9f707b94c09','::1',NULL,'2026-06-18 14:21:10','2026-06-11 23:21:10'),(119,9,'2dd055b3118fb261c5f93015fd2325ceb98cc3075d7623a2cca3e75d53d64397','::1',NULL,'2026-06-18 14:21:22','2026-06-11 23:21:22'),(120,9,'19ffad8a92ebbc6c878facc18c6bef4bd774cd388d913d532aa9db77a0083c74','::1',NULL,'2026-06-18 14:21:24','2026-06-11 23:21:24'),(121,6,'e84efabd39d1000f55e2f9ee78fcc709c3838e97f786729bb03fa5cef41e7867','::1',NULL,'2026-06-18 14:21:44','2026-06-11 23:21:44'),(122,6,'8e45b9cbe5e59370c7d70f352170346115bff1a51b0c5d0a62c5fe3d6cccf37e','::1',NULL,'2026-06-18 14:21:47','2026-06-11 23:21:47'),(123,6,'bc235a8966521b028358fc1ee6e77618b8d9597f30d9258127c2439214f88f65','::1',NULL,'2026-06-18 14:21:52','2026-06-11 23:21:52'),(124,6,'20c640597da6179a7ffae92e037715e4f5d90b578084153c0fbcc1ec7f4b6fc0','::1',NULL,'2026-06-18 14:21:56','2026-06-11 23:21:56'),(125,9,'5f501c1fff29ca52dd149ac027973af74dd89b6de77c88e6dde8f6da65c28892','::1',NULL,'2026-06-18 14:22:45','2026-06-11 23:22:45'),(126,5,'47d5da1329513cb45400b687ea2d78559e2ed7b8d23f0241d8bf3b973ec3e607','::1',NULL,'2026-06-18 14:23:20','2026-06-11 23:23:20'),(127,5,'743d7bcfe706c09e3fcaaaa17d765bebc68a0f129f2aeb5e57e5de5961894773','::1',NULL,'2026-06-18 14:23:32','2026-06-11 23:23:32'),(128,8,'51f216dadb8b28228546d36c5c9571512a94a51e7d7208d67766a77ccdb4a034','::1',NULL,'2026-06-18 14:31:16','2026-06-11 23:31:16');
UNLOCK TABLES;


DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `ticket_replies` WRITE;
UNLOCK TABLES;


DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `status` enum('new','in_progress','answered','closed') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


LOCK TABLES `tickets` WRITE;
UNLOCK TABLES;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'bcrypt хеш пароля',
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL COMMENT 'Адрес участка в СНТ',
  `role_id` int(11) NOT NULL DEFAULT 5 COMMENT 'ID роли (по умолчанию член)',
  `status` enum('active','inactive','pending') DEFAULT 'pending' COMMENT 'статус в системе',
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Члены СНТ';


LOCK TABLES `users` WRITE;
INSERT INTO `users` VALUES (1,'admin@snt.ru','$2y$10$V9DX0xN5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5','Администратор','+79001234567','Участок №28',1,'active',NULL,'2026-06-04 06:00:10','2026-06-11 22:59:06',NULL),(2,'chairman@snt.ru','$2y$10$V9DX0xN5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5','Иван Петров','+79001234568','Участок 1',2,'active',NULL,'2026-06-04 06:00:10','2026-06-04 06:00:10',NULL),(3,'treasurer@snt.ru','$2y$10$V9DX0xN5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5','Мария Сидорова','+79001234569','Участок 2',3,'active',NULL,'2026-06-04 06:00:10','2026-06-04 06:00:10',NULL),(4,'secretary@snt.ru','$2y$10$V9DX0xN5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5X5','Елена Иванова','+79001234570','Участок 3',4,'active',NULL,'2026-06-04 06:00:10','2026-06-04 06:00:10',NULL),(5,'admin@snt-berezka.ru','$2y$10$PSdVfA45Xr1QU4CvSl8o.eSAzoK4Ku2njORTlS43ggJ2boSKGpYcq','Администратор Системы','+7-914-001-0001','Правление',1,'active',NULL,'2026-06-04 06:09:51','2026-06-11 23:23:32','2026-06-11 23:23:32'),(6,'chairman@snt-berezka.ru','$2y$10$YVi.ZgitrL/xhGpcqZlcUO./aXQYf5e4YBC3EjAYAO0wAEGZO4To2','Иванов Пётр Сергеевич','+7-914-001-0002','Участок №1',2,'active',NULL,'2026-06-04 06:09:51','2026-06-11 23:21:56','2026-06-11 23:21:56'),(7,'treasurer@snt-berezka.ru','$2y$10$MadOrIP/NsPBi.SGMsw3LOT3YXg1u3r5BK1BFDHeJQav9OfGYx0i2','Петрова Галина Ивановна','+7-914-001-0003','Участок №12',3,'active',NULL,'2026-06-04 06:09:51','2026-06-11 23:17:24','2026-06-11 23:17:24'),(8,'secretary@snt-berezka.ru','$2y$10$s2R3wIaPJJwAL9YiMh1GIOwZru9HQUKCk9aRtqoYgi2b6dJcqbFyC','Сидоров Алексей Николаевич','+7-914-001-0004','Участок №25',4,'active',NULL,'2026-06-04 06:09:51','2026-06-11 23:31:16','2026-06-11 23:31:16'),(9,'member@snt-berezka.ru','$2y$10$XfV6z1rLLyXQWkEeDLRZJ.LxIMEpBQCq8rdiD3Ri2E0kKRN1PWoNe','Козлова Надежда Петровна','+7-914-001-0005','Участок №47',5,'active',NULL,'2026-06-04 06:09:51','2026-06-11 23:22:45','2026-06-11 23:22:45'),(10,'guest@snt-berezka.ru','$2y$10$zjI8yxZ2xYnQqtAuB.apKefV.7GHlztoG/DAoCOajVc/WEhpLOH32','Гостевой Пользователь','+7-914-001-0006','',6,'active',NULL,'2026-06-04 06:09:51','2026-06-12 23:10:50',NULL);
UNLOCK TABLES;


DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','active','closed') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `results_visible` tinyint(1) DEFAULT 0 COMMENT 'Видны ли результаты',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Голосования и решения';


LOCK TABLES `votes` WRITE;
INSERT INTO `votes` VALUES (7,'Когда провести субботник?','Выберите удобную дату для общего субботника','active',2,'2026-06-11 22:06:49','2026-06-11 22:06:49','2026-07-11 22:06:49',1),(8,'утверждение бюджета','','active',5,'2026-06-11 22:21:41','2026-06-11 22:21:41',NULL,0);
UNLOCK TABLES;


DROP TABLE IF EXISTS `votes_options`;
CREATE TABLE `votes_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `vote_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_vote` (`vote_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Варианты ответов в голосованиях';


LOCK TABLES `votes_options` WRITE;
INSERT INTO `votes_options` VALUES (17,7,'22 июня (суббота)',0),(18,7,'29 июня (суббота)',1),(19,7,'Воздержаться / Перенести',0),(20,8,'за',1),(21,8,'против',0),(22,8,'воздержусь',0);
UNLOCK TABLES;


DROP TABLE IF EXISTS `votes_participants`;
CREATE TABLE `votes_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`vote_id`,`user_id`) COMMENT 'Один голос на человека',
  KEY `option_id` (`option_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Голоса участников';


LOCK TABLES `votes_participants` WRITE;
INSERT INTO `votes_participants` VALUES (5,8,8,20,'2026-06-11 22:26:26'),(6,7,8,18,'2026-06-11 22:26:30');
UNLOCK TABLES;


SET FOREIGN_KEY_CHECKS=1;

