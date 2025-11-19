<?php

namespace App\Models;

class Category extends BaseModel
{
    protected static string $table = 'categories';
    protected static bool $usesTenant = true;
    protected static bool $usesSoftDeletes = true;
    protected static array $fillable = [
        'tenant_id', 'name', 'name_ar', 'slug', 'description',
        'description_ar', 'image_url', 'sort_order', 'is_active'
    ];

    /**
     * Get categories with item count
     */
    public static function allWithItemCount(): array
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        $sql = "SELECT c.*, COUNT(m.id) as items_count
                FROM `categories` c
                LEFT JOIN `menu_items` m ON c.id = m.category_id AND m.deleted_at IS NULL
                WHERE c.tenant_id = :tenant_id AND c.deleted_at IS NULL
                GROUP BY c.id
                ORDER BY c.sort_order ASC";

        return self::query($sql, ['tenant_id' => $tenantId]);
    }
}
