<?php

namespace App\Models;

class MenuItem extends BaseModel
{
    protected static string $table = 'menu_items';
    protected static bool $usesTenant = true;
    protected static bool $usesSoftDeletes = true;
    protected static array $fillable = [
        'tenant_id', 'category_id', 'name', 'name_ar', 'slug',
        'description', 'description_ar', 'price', 'image_url',
        'calories', 'preparation_time', 'is_available', 'is_featured',
        'is_vegetarian', 'is_vegan', 'is_gluten_free', 'sort_order'
    ];

    /**
     * Get menu item with modifiers
     */
    public static function findWithModifiers(int $id): ?array
    {
        $item = self::find($id);

        if (!$item) {
            return null;
        }

        // Get modifiers
        $sql = "SELECT m.*, GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', mo.id,
                        'name', mo.name,
                        'price_modifier', mo.price_modifier,
                        'is_default', mo.is_default
                    )
                ) as options
                FROM `item_modifiers` m
                LEFT JOIN `modifier_options` mo ON m.id = mo.modifier_id
                WHERE m.item_id = :item_id
                GROUP BY m.id";

        $item['modifiers'] = self::query($sql, ['item_id' => $id]);

        return $item;
    }

    /**
     * Get menu items by category
     */
    public static function getByCategory(int $categoryId): array
    {
        return self::where('category_id', $categoryId);
    }
}
