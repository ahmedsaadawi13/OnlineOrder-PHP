<?php

namespace App\Models;

class Restaurant extends BaseModel
{
    protected static string $table = 'restaurants';
    protected static bool $usesTenant = false; // Restaurants ARE tenants
    protected static bool $usesSoftDeletes = true;
    protected static array $fillable = [
        'name', 'slug', 'email', 'phone', 'logo_url',
        'currency', 'timezone', 'status', 'metadata'
    ];

    /**
     * Find restaurant by slug
     */
    public static function findBySlug(string $slug): ?array
    {
        return self::whereOne('slug', $slug);
    }

    /**
     * Get restaurant with settings
     */
    public static function findWithSettings(int $id): ?array
    {
        $sql = "SELECT r.*, s.*
                FROM `restaurants` r
                LEFT JOIN `restaurant_settings` s ON r.id = s.tenant_id
                WHERE r.id = :id AND r.deleted_at IS NULL
                LIMIT 1";

        return self::queryOne($sql, ['id' => $id]);
    }
}
