<?php

namespace App\Models;

class Order extends BaseModel
{
    protected static string $table = 'orders';
    protected static bool $usesTenant = true;
    protected static array $fillable = [
        'tenant_id', 'customer_id', 'branch_id', 'order_number', 'status',
        'payment_status', 'order_type', 'subtotal', 'tax_amount',
        'delivery_fee', 'discount_amount', 'total_amount', 'coupon_code',
        'delivery_address_id', 'scheduled_at', 'estimated_delivery_time',
        'special_instructions'
    ];

    /**
     * Get order with items and customer
     */
    public static function findWithDetails(int $id): ?array
    {
        $tenantId = $_SESSION['tenant_id'] ?? 0;

        $sql = "SELECT o.*, c.first_name, c.last_name, c.phone, c.email,
                       b.name as branch_name
                FROM `orders` o
                LEFT JOIN `customers` c ON o.customer_id = c.id
                LEFT JOIN `branches` b ON o.branch_id = b.id
                WHERE o.id = :id AND o.tenant_id = :tenant_id
                LIMIT 1";

        $order = self::queryOne($sql, ['id' => $id, 'tenant_id' => $tenantId]);

        if (!$order) {
            return null;
        }

        // Get order items
        $itemsSql = "SELECT oi.*, mi.name as item_name_original
                     FROM `order_items` oi
                     LEFT JOIN `menu_items` mi ON oi.item_id = mi.id
                     WHERE oi.order_id = :order_id";

        $order['items'] = self::query($itemsSql, ['order_id' => $id]);

        // Get status history
        $historySql = "SELECT * FROM `order_status_history`
                       WHERE order_id = :order_id
                       ORDER BY created_at ASC";

        $order['status_history'] = self::query($historySql, ['order_id' => $id]);

        return $order;
    }

    /**
     * Create order with items
     */
    public static function createOrder(array $orderData, array $items): int
    {
        // Begin transaction
        Database::beginTransaction();

        try {
            // Create order
            $orderId = self::create($orderData);

            // Create order items
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                Database::insert('order_items', $item);
            }

            // Add to status history
            Database::insert('order_status_history', [
                'order_id' => $orderId,
                'status' => $orderData['status'],
                'notes' => 'Order created',
                'created_at' => now()
            ]);

            Database::commit();

            return $orderId;
        } catch (\Exception $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Update order status
     */
    public static function updateStatus(int $id, string $status, ?string $notes = null, ?int $userId = null): bool
    {
        // Update order
        self::update($id, ['status' => $status, 'updated_at' => now()]);

        // Add to history
        Database::insert('order_status_history', [
            'order_id' => $id,
            'status' => $status,
            'notes' => $notes,
            'created_by' => $userId,
            'created_at' => now()
        ]);

        return true;
    }
}
