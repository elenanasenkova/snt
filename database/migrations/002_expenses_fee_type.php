<?php
$db = \services\DatabaseConnection::get();

// Добавляем fee_type_id в finances (если ещё нет)
$db->query("ALTER TABLE finances ADD COLUMN IF NOT EXISTS fee_type_id INT NULL DEFAULT NULL COMMENT 'Категория взноса, из которой трата' AFTER type");

// Индекс для быстрой выборки по категории
$db->query("ALTER TABLE finances ADD INDEX IF NOT EXISTS idx_fee_type_id (fee_type_id)");
