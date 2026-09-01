<?php
/**
 * Lớp Database Singleton kết nối PDO MySQL
 */

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Lấy kết nối PDO Singleton
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
                } else {
                    die('Hệ thống đang bảo trì. Vui lòng quay lại sau.');
                }
            }
        }

        return self::$instance;
    }

    /**
     * Thực thi truy vấn prepared statement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy 1 bản ghi
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Lấy danh sách tất cả bản ghi
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Thêm mới bản ghi và trả về ID vừa tạo
     */
    public static function insert(string $table, array $data): int {
        $columns = array_keys($data);
        $fields = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        self::query($sql, array_values($data));

        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Cập nhật bản ghi
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        $values = [];

        foreach ($data as $col => $val) {
            $setParts[] = "`{$col}` = ?";
            $values[] = $val;
        }

        $setSql = implode(', ', $setParts);
        $sql = "UPDATE `{$table}` SET {$setSql} WHERE {$where}";

        $mergedParams = array_merge($values, $whereParams);
        $stmt = self::query($sql, $mergedParams);

        return $stmt->rowCount();
    }

    /**
     * Xóa bản ghi
     */
    public static function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }
}
