<?php
/**
 * Database Configuration
 * PHP 8.1+ with MySQLi
 */

class Database
{
    private static ?mysqli $instance = null;
    private mysqli $connection;

    // Database configuration
    private const HOST = 'localhost';
    private const USER = 'root';
    private const PASS = '';
    private const NAME = 'surat_app';
    private const CHARSET = 'utf8mb4';

    private function __construct()
    {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $this->connection = new mysqli(
                self::HOST,
                self::USER,
                self::PASS,
                self::NAME
            );

            $this->connection->set_charset(self::CHARSET);

        } catch (mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance(): mysqli
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }

    /**
     * Close database connection
     */
    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->connection->close();
            self::$instance = null;
        }
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollback();
    }

    /**
     * Get last insert ID
     */
    public static function lastInsertId(): int
    {
        return self::getInstance()->insert_id;
    }

    /**
     * Escape string
     */
    public static function escape(string $string): string
    {
        return self::getInstance()->real_escape_string($string);
    }

    /**
     * Check if table exists
     */
    public static function tableExists(string $tableName): bool
    {
        $result = self::getInstance()->query(
            "SHOW TABLES LIKE '" . self::escape($tableName) . "'"
        );

        return $result->num_rows > 0;
    }

    /**
     * Execute query and return result
     */
    public static function query(string $sql, ?array $params = null): mysqli_result|bool
    {
        $db = self::getInstance();

        if ($params === null) {
            return $db->query($sql);
        }

        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $db->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Fetch single row
     */
    public static function fetchOne(string $sql, ?array $params = null): ?array
    {
        $result = self::query($sql, $params);

        if ($result === false) {
            return null;
        }

        return $result->fetch_assoc();
    }

    /**
     * Fetch multiple rows
     */
    public static function fetchAll(string $sql, ?array $params = null): array
    {
        $result = self::query($sql, $params);

        if ($result === false) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetch single value
     */
    public static function fetchValue(string $sql, ?array $params = null): mixed
    {
        $row = self::fetchOne($sql, $params);

        return $row ? array_values($row)[0] : null;
    }

    /**
     * Insert record and return ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $values = array_values($data);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        self::query($sql, $values);

        return self::lastInsertId();
    }

    /**
     * Update record
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $where
        );

        $values = array_merge($values, $whereParams);

        self::query($sql, $values);

        return self::getInstance()->affected_rows;
    }

    /**
     * Delete record
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM $table WHERE $where";

        self::query($sql, $params);

        return self::getInstance()->affected_rows;
    }

    /**
     * Get database error
     */
    public static function getError(): string
    {
        return self::getInstance()->error;
    }

    /**
     * Check if connection is alive
     */
    public static function isConnected(): bool
    {
        try {
            return self::getInstance()->ping();
        } catch (Exception $e) {
            return false;
        }
    }
}

// Auto-close connection on script end
register_shutdown_function(function () {
    Database::close();
});
?>