<?php
namespace services;

class DatabaseConnection {
    private static $connection = null;

    public static function get(): \mysqli {
        if (self::$connection === null) {
            self::$connection = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$connection->connect_error) {
                error_log('DB connect error: ' . self::$connection->connect_error);
                die('Database connection failed');
            }
            self::$connection->set_charset('utf8mb4');
            self::$connection->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            self::$connection->query("SET CHARACTER SET 'utf8mb4'");
        }
        return self::$connection;
    }
}
