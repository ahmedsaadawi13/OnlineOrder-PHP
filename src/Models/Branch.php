<?php

namespace App\Models;

use App\Core\Database;

class Branch extends BaseModel
{
    protected static string $table = 'branches';
    protected static bool $usesTenant = true;
    protected static bool $usesTimestamps = true;
    protected static bool $usesSoftDeletes = true;

    protected static array $fillable = [
        'tenant_id', 'name', 'address_line1', 'address_line2', 'city',
        'state', 'postal_code', 'country', 'latitude', 'longitude',
        'phone', 'email', 'is_active', 'accepts_online_orders'
    ];

    /**
     * Get branch with opening hours
     */
    public static function findWithHours(int $id): ?array
    {
        $branch = static::find($id);

        if (!$branch) {
            return null;
        }

        // Get opening hours
        $hours = Database::query(
            "SELECT * FROM opening_hours WHERE branch_id = :branch_id ORDER BY day_of_week",
            ['branch_id' => $id]
        );

        $branch['opening_hours'] = $hours;

        return $branch;
    }

    /**
     * Get active branches that accept online orders
     */
    public static function getActiveBranches(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? ($_SESSION['tenant_id'] ?? null);

        if (!$tenantId) {
            return [];
        }

        $sql = "SELECT * FROM " . static::$table . "
                WHERE tenant_id = :tenant_id
                AND is_active = 1
                AND accepts_online_orders = 1
                AND deleted_at IS NULL
                ORDER BY name ASC";

        return Database::query($sql, ['tenant_id' => $tenantId]);
    }

    /**
     * Check if branch is currently open
     */
    public static function isOpen(int $branchId): bool
    {
        $currentDay = (int) date('w'); // 0 = Sunday, 6 = Saturday
        $currentTime = date('H:i:s');

        $hours = Database::queryOne(
            "SELECT * FROM opening_hours
             WHERE branch_id = :branch_id
             AND day_of_week = :day_of_week
             AND is_closed = 0",
            [
                'branch_id' => $branchId,
                'day_of_week' => $currentDay
            ]
        );

        if (!$hours) {
            return false;
        }

        return $currentTime >= $hours['open_time'] && $currentTime <= $hours['close_time'];
    }

    /**
     * Get branch stats
     */
    public static function getStats(int $branchId): array
    {
        // Total orders
        $totalOrders = Database::queryOne(
            "SELECT COUNT(*) as count FROM orders WHERE branch_id = :branch_id",
            ['branch_id' => $branchId]
        );

        // Today's orders
        $todayOrders = Database::queryOne(
            "SELECT COUNT(*) as count FROM orders
             WHERE branch_id = :branch_id
             AND DATE(created_at) = CURDATE()",
            ['branch_id' => $branchId]
        );

        // Total revenue
        $totalRevenue = Database::queryOne(
            "SELECT SUM(total_amount) as total FROM orders
             WHERE branch_id = :branch_id
             AND status = 'completed'
             AND payment_status = 'completed'",
            ['branch_id' => $branchId]
        );

        return [
            'total_orders' => (int) ($totalOrders['count'] ?? 0),
            'today_orders' => (int) ($todayOrders['count'] ?? 0),
            'total_revenue' => (float) ($totalRevenue['total'] ?? 0)
        ];
    }
}
