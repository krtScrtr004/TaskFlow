<?php 

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Connection {
    private static ?PDO $instance = null;

    private function __construct() {}

    /**
     * Returns a shared PDO instance (Singleton) configured for the application's MySQL database.
     *
     * This method lazily initializes and caches a single PDO connection for reuse across the
     * application. If an instance already exists, the cached instance is returned.
     *
     * Connection details and behavior:
     * - Host: localhost
     * - Database: taskflow
     * - User: root
     * - Password: (empty string)
     * - Charset: utf8mb4
     * - DSN: mysql:host=...;dbname=...;charset=...
     *
     * PDO options applied:
     * - PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION (exceptions on errors)
     * - PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC (associative arrays by default)
     * - PDO::ATTR_EMULATE_PREPARES => false (use native prepared statements where possible)
     *
     * After establishing the connection, the server time zone is set to '+08:00' via:
     *     SET time_zone = '+08:00'
     *
     * Behavior on failure:
     * - If PDO construction fails, the underlying PDOException is caught and rethrown as a
     *   RuntimeException with a descriptive message.
     *
     * Note: No parameters are accepted — connection settings are hard-coded in the method.
     *
     * @return PDO The shared PDO instance ready for use.
     *
     * @throws RuntimeException If the database connection cannot be established.
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $host = 'localhost';
            $db = 'taskflow';
            $user = 'root';
            $pass = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                self::$instance->exec("SET time_zone = '+08:00'");
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }
}