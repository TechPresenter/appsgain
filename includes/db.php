<?php
/**
 * APPSGAIN CMS — PDO Database Connection (Singleton)
 */

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (DEBUG) {
                    die('Database connection failed: ' . $e->getMessage());
                }
                die('Service temporarily unavailable. Please try again later.');
            }
        }
        return self::$instance;
    }
}

// Convenience alias
function db(): PDO {
    return Database::getInstance();
}

/**
 * Execute a query and return all rows.
 */
function dbFetchAll(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return a single row.
 */
function dbFetchOne(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Execute a query and return a single column value.
 */
function dbFetchValue(string $sql, array $params = []): mixed {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Execute an INSERT/UPDATE/DELETE and return affected rows.
 */
function dbExecute(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Execute an INSERT and return the last inserted ID.
 */
function dbInsert(string $sql, array $params = []): string {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return db()->lastInsertId();
}

/**
 * Alias for dbFetchOne — returns a single row or null.
 */
function dbFetchRow(string $sql, array $params = []): ?array {
    return dbFetchOne($sql, $params);
}

/**
 * Build and execute a simple INSERT from a key→value array.
 */
function dbInsertRow(string $table, array $data): string {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO `$table` ($cols) VALUES ($placeholders)";
    return dbInsert($sql, array_values($data));
}

/**
 * Build and execute a simple UPDATE from a key→value array.
 */
function dbUpdateRow(string $table, array $data, string $where, array $whereParams = []): int {
    $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
    $sql = "UPDATE `$table` SET $set WHERE $where";
    return dbExecute($sql, array_merge(array_values($data), $whereParams));
}

/**
 * Simple wrapper for UPDATE by ID.
 */
function dbUpdate(string $table, array $data, string $idField, mixed $idValue): int {
    return dbUpdateRow($table, $data, "`$idField` = ?", [$idValue]);
}

/**
 * Simple wrapper for DELETE by ID.
 */
function dbDelete(string $table, string $idField, mixed $idValue): int {
    $sql = "DELETE FROM `$table` WHERE `$idField` = ?";
    return dbExecute($sql, [$idValue]);
}

