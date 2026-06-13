<?php
// Колонка plot_scope для видов взносов: 'all' — начисляется всем участкам (поведение по умолчанию),
// 'selected' — только выбранным (записи fee_payments создаются при создании вида взноса).
$db = \services\DatabaseConnection::get();

$col = $db->query("
    SELECT COUNT(*) AS c
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fee_types'
      AND COLUMN_NAME = 'plot_scope'
")->fetch_assoc();

if ((int)($col['c'] ?? 0) === 0) {
    $db->query("ALTER TABLE fee_types
        ADD COLUMN plot_scope VARCHAR(10) NOT NULL DEFAULT 'all'
        COMMENT 'all — всем участкам, selected — только выбранным' AFTER amount");
}
