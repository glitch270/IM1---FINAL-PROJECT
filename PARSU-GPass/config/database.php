<?php
/**
 * Database Connection Configuration (PDO MySQL / MariaDB)
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'parsu_gpass');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance
 *
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error in production and present clean message
            error_log("Database connection failed: " . $e->getMessage());
            die("Database Connection Error: Unable to connect to the PARSU-GPass database. Please verify that MySQL/MariaDB is running in XAMPP and that 'parsu_gpass' is imported.");
        }
    }

    return $pdo;
}

// Global variable support for procedural scripts
$pdo = getDBConnection();

