<?php

/**
 * Database Class
 * ==============
 * Singleton MySQLi connection wrapper.
 * Credentials are read from config.json via get_config() — NEVER hardcoded.
 *
 * Usage:
 *   $db = Database::getConnection();
 *   $result = $db->query("SELECT ...");
 */
class Database
{
    /** @var mysqli|null Shared single connection */
    public static $conn = null;

    /**
     * Returns the shared MySQLi connection, creating it on first call.
     *
     * @return mysqli
     */
    public static function getConnection(): mysqli
    {
        if (Database::$conn === null) {
            $host     = get_config('db_server');
            $username = get_config('db_username');
            $password = get_config('db_password');
            $dbname   = get_config('db_name');

            $connection = new mysqli($host, $username, $password, $dbname);

            if ($connection->connect_error) {
                // In production, swap this for proper error logging
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed.']));
            }

            $connection->set_charset('utf8mb4');
            Database::$conn = $connection;
        }

        return Database::$conn;
    }

    /**
     * Close the connection (call at end of long-running scripts).
     */
    public static function close(): void
    {
        if (Database::$conn !== null) {
            Database::$conn->close();
            Database::$conn = null;
        }
    }
}
