<?php
/**
 * Database Configuration
 * PHP 8.1+ with MySQLi
 */
class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;

    private const HOST = 'localhost';
    private const USER = 'root';
    private const PASS = '';
    private const NAME = 'surat_app';
    private const PORT = 3308;
    private const CHARSET = 'utf8mb4';

    private function __construct()
    {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->connection = new mysqli(self::HOST, self::USER, self::PASS, self::NAME, self::PORT);
            $this->connection->set_charset(self::CHARSET);
        } catch (mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Tidak dapat terhubung ke database.");
        }
    }

    public static function getInstance(): mysqli
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    /**
     * Helper method to execute a prepared statement query.
     */
    private static function executeQuery(string $sql, ?array $params = null): mysqli_result|bool
    {
        $db = self::getInstance();

        // For queries without parameters
        if ($params === null) {
            return $db->query($sql);
        }

        // For queries with parameters (prepared statement)
        try {
            $stmt = $db->prepare($sql);
            // Dynamically bind parameters
            if (!empty($params)) {
                $types = str_repeat('s', count($params)); // Treat all params as strings for simplicity
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result();
        } catch (mysqli_sql_exception $e) {
            error_log("Database query failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    /**
     * Fetch multiple rows from the database.
     */
    public static function fetchAll(string $sql, ?array $params = null): array
    {
        $result = self::executeQuery($sql, $params);
        if ($result === false) {
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetch a single row from the database.
     */
    public static function fetchOne(string $sql, ?array $params = null): ?array
    {
        $result = self::executeQuery($sql, $params);
        if ($result === false) {
            return null;
        }
        return $result->fetch_assoc();
    }

    /**
     * Fetch a single value from the first column of the first row.
     */
    public static function fetchValue(string $sql, ?array $params = null): mixed
    {
        $row = self::fetchOne($sql, $params);
        return $row ? array_values($row)[0] : null;
    }
}
?>