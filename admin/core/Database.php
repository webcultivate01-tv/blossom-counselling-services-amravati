<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Keep NOW()/CURDATE() in the same time zone as PHP so "today" means the same everywhere.
            try {
                self::$connection->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
            } catch (Throwable $e) {
                error_log('[Database] could not set time zone: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
