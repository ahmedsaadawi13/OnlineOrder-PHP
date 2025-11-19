<?php

namespace App\Controllers\Order;

use App\Core\Request;
use App\Core\Response;
use App\Models\Order;
use App\Core\Database;

class OrderController
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * List all orders
     */
    public function index(Request $request)
    {
        $tenantId = $request->tenantId;
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $page = (int) ($request->query('page') ?? 1);
        $perPage = min((int) ($request->query('per_page') ?? 15), 100);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT o.*, c.first_name, c.last_name, c.phone,
                       b.name as branch_name,
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN branches b ON o.branch_id = b.id
                WHERE o.tenant_id = :tenant_id";

        $params = ['tenant_id' => $tenantId];

        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }

        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT {$perPage} OFFSET {$offset}";

        $orders = Order::query($sql, $params);

        // Get total count
        $countSql = str_replace('SELECT o.*, c.first_name, c.last_name, c.phone, b.name as branch_name, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count', 'SELECT COUNT(*) as total', $sql);
        $countSql = substr($countSql, 0, strpos($countSql, 'ORDER BY'));
        $total = Order::queryOne($countSql, $params)['total'] ?? 0;

        return $this->response->json($orders, 200, null);
    }

    /**
     * Get order details
     */
    public function show(Request $request)
    {
        $id = $request->param('id');
        $order = Order::findWithDetails($id);

        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        return $this->response->json($order, 200);
    }

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'customer_id', 'branch_id', 'order_type', 'delivery_address_id',
            'special_instructions', 'scheduled_at', 'coupon_code'
        ]);

        $items = $request->input('items', []);

        if (empty($items)) {
            return $this->response->error('Order must have at least one item', 422);
        }

        if (!$data['branch_id']) {
            return $this->response->error('Branch is required', 422);
        }

        try {
            // Calculate order totals
            $subtotal = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $menuItem = \App\Models\MenuItem::find($item['item_id']);

                if (!$menuItem) {
                    return $this->response->error("Item {$item['item_id']} not found", 404);
                }

                $quantity = $item['quantity'] ?? 1;
                $unitPrice = $menuItem['price'];

                // Add modifier prices
                if (!empty($item['modifiers'])) {
                    // TODO: Calculate modifier prices
                }

                $itemSubtotal = $unitPrice * $quantity;
                $subtotal += $itemSubtotal;

                $orderItems[] = [
                    'item_id' => $item['item_id'],
                    'item_name' => $menuItem['name'],
                    'item_name_ar' => $menuItem['name_ar'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'selected_modifiers' => json_encode($item['modifiers'] ?? []),
                    'special_instructions' => $item['special_instructions'] ?? null,
                    'created_at' => now()
                ];
            }

            // Get restaurant settings for tax
            $tenantId = $request->tenantId;
            $settings = Database::queryOne(
                "SELECT * FROM restaurant_settings WHERE tenant_id = :tenant_id",
                ['tenant_id' => $tenantId]
            );

            $taxRate = $settings['tax_rate'] ?? 0;
            $taxAmount = $subtotal * ($taxRate / 100);
            $deliveryFee = $data['order_type'] === 'delivery' ? 5.00 : 0; // TODO: Calculate based on distance
            $discountAmount = 0; // TODO: Apply coupon

            $total = $subtotal + $taxAmount + $deliveryFee - $discountAmount;

            // Prepare order data
            $orderData = [
                'tenant_id' => $tenantId,
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'order_number' => generateOrderNumber(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'order_type' => $data['order_type'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'total_amount' => $total,
                'coupon_code' => $data['coupon_code'] ?? null,
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'estimated_delivery_time' => $data['order_type'] === 'delivery'
                    ? date('Y-m-d H:i:s', strtotime('+45 minutes'))
                    : date('Y-m-d H:i:s', strtotime('+20 minutes')),
                'created_at' => now(),
                'updated_at' => now()
            ];

            $orderId = Order::createOrder($orderData, $orderItems);

            $order = Order::findWithDetails($orderId);

            return $this->response->json($order, 201, 'Order created successfully');

        } catch (\Exception $e) {
            logError('Failed to create order: ' . $e->getMessage());
            return $this->response->error('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request)
    {
        $id = $request->param('id');
        $status = $request->input('status');
        $notes = $request->input('notes');

        $order = Order::find($id);

        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            return $this->response->error('Invalid status', 422);
        }

        try {
            $userId = $request->user['id'] ?? null;
            Order::updateStatus($id, $status, $notes, $userId);

            $order = Order::findWithDetails($id);

            return $this->response->json($order, 200, 'Order status updated successfully');

        } catch (\Exception $e) {
            logError('Failed to update order status: ' . $e->getMessage());
            return $this->response->error('Failed to update order status', 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request)
    {
        $id = $request->param('id');
        $reason = $request->input('reason');

        $order = Order::find($id);

        if (!$order) {
            return $this->response->error('Order not found', 404);
        }

        if (!in_array($order['status'], ['pending', 'confirmed'])) {
            return $this->response->error('Cannot cancel order in current status', 422);
        }

        try {
            Order::update($id, [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now()
            ]);

            // Add to status history
            $userId = $request->user['id'] ?? null;
            Order::updateStatus($id, 'cancelled', $reason, $userId);

            return $this->response->json(null, 200, 'Order cancelled successfully');

        } catch (\Exception $e) {
            logError('Failed to cancel order: ' . $e->getMessage());
            return $this->response->error('Failed to cancel order', 500);
        }
    }
}
