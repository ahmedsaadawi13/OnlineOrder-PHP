<?php

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static bool $usesTenant = false;
    protected static array $fillable = [];
    protected static bool $usesTimestamps = true;
    protected static bool $usesSoftDeletes = false;

    /**
     * Find record by ID
     */
    public static function find(int $id): ?array
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        $sql = "SELECT * FROM `{$table}` WHERE `{$pk}` = :id";

        if (static::$usesSoftDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }

        $sql .= " LIMIT 1";

        return Database::queryOne($sql, ['id' => $id]) ?: null;
    }

    /**
     * Find all records
     */
    public static function all(array $conditions = [], int $limit = 1000, int $offset = 0): array
    {
        $table = static::$table;
        $sql = "SELECT * FROM `{$table}`";

        $where = [];
        $params = [];

        // Add tenant filter if model uses tenants
        if (static::$usesTenant && isset($_SESSION['tenant_id'])) {
            $where[] = "`tenant_id` = :tenant_id";
            $params['tenant_id'] = $_SESSION['tenant_id'];
        }

        // Add conditions
        foreach ($conditions as $field => $value) {
            $where[] = "`{$field}` = :{$field}";
            $params[$field] = $value;
        }

        // Add soft deletes filter
        if (static::$usesSoftDeletes) {
            $where[] = "`deleted_at` IS NULL";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        return Database::query($sql, $params);
    }

    /**
     * Create new record
     */
    public static function create(array $data): int
    {
        $table = static::$table;

        // Add tenant_id if model uses tenants
        if (static::$usesTenant && isset($_SESSION['tenant_id'])) {
            $data['tenant_id'] = $_SESSION['tenant_id'];
        }

        // Add timestamps
        if (static::$usesTimestamps) {
            $data['created_at'] = now();
            $data['updated_at'] = now();
        }

        // Filter to fillable fields
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        return Database::insert($table, $data);
    }

    /**
     * Update record
     */
    public static function update(int $id, array $data): int
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        // Add updated_at timestamp
        if (static::$usesTimestamps) {
            $data['updated_at'] = now();
        }

        // Filter to fillable fields
        if (!empty(static::$fillable)) {
            $data = array_intersect_key($data, array_flip(static::$fillable));
        }

        $where = "`{$pk}` = :id";
        $params = ['id' => $id];

        // Add tenant filter if model uses tenants
        if (static::$usesTenant && isset($_SESSION['tenant_id'])) {
            $where .= " AND `tenant_id` = :tenant_id";
            $params['tenant_id'] = $_SESSION['tenant_id'];
        }

        return Database::update($table, $data, $where, $params);
    }

    /**
     * Delete record (soft or hard delete)
     */
    public static function delete(int $id): int
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        if (static::$usesSoftDeletes) {
            // Soft delete
            return static::update($id, ['deleted_at' => now()]);
        }

        // Hard delete
        $where = "`{$pk}` = :id";
        $params = ['id' => $id];

        // Add tenant filter if model uses tenants
        if (static::$usesTenant && isset($_SESSION['tenant_id'])) {
            $where .= " AND `tenant_id` = :tenant_id";
            $params['tenant_id'] = $_SESSION['tenant_id'];
        }

        return Database::delete($table, $where, $params);
    }

    /**
     * Find where condition
     */
    public static function where(string $field, $value): array
    {
        return static::all([$field => $value]);
    }

    /**
     * Find one where condition
     */
    public static function whereOne(string $field, $value): ?array
    {
        $results = static::where($field, $value);
        return $results[0] ?? null;
    }

    /**
     * Execute custom query
     */
    public static function query(string $sql, array $params = []): array
    {
        return Database::query($sql, $params);
    }

    /**
     * Execute custom query and return one result
     */
    public static function queryOne(string $sql, array $params = [])
    {
        return Database::queryOne($sql, $params);
    }

    /**
     * Count records
     */
    public static function count(array $conditions = []): int
    {
        $table = static::$table;
        $sql = "SELECT COUNT(*) as count FROM `{$table}`";

        $where = [];
        $params = [];

        // Add tenant filter
        if (static::$usesTenant && isset($_SESSION['tenant_id'])) {
            $where[] = "`tenant_id` = :tenant_id";
            $params['tenant_id'] = $_SESSION['tenant_id'];
        }

        // Add conditions
        foreach ($conditions as $field => $value) {
            $where[] = "`{$field}` = :{$field}";
            $params[$field] = $value;
        }

        // Add soft deletes filter
        if (static::$usesSoftDeletes) {
            $where[] = "`deleted_at` IS NULL";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = Database::queryOne($sql, $params);
        return (int) ($result['count'] ?? 0);
    }
}
