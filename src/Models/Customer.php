<?php

namespace App\Models;

class Customer extends BaseModel
{
    protected static string $table = 'customers';
    protected static bool $usesTenant = true;
    protected static bool $usesSoftDeletes = true;
    protected static array $fillable = [
        'tenant_id', 'first_name', 'last_name', 'email', 'phone',
        'password', 'avatar_url', 'date_of_birth', 'preferred_language', 'is_active'
    ];

    /**
     * Find customer by email
     */
    public static function findByEmail(string $email, ?int $tenantId = null): ?array
    {
        $sql = "SELECT * FROM `customers` WHERE `email` = :email";
        $params = ['email' => $email];

        if ($tenantId) {
            $sql .= " AND `tenant_id` = :tenant_id";
            $params['tenant_id'] = $tenantId;
        }

        $sql .= " AND `deleted_at` IS NULL LIMIT 1";

        return self::queryOne($sql, $params);
    }

    /**
     * Create customer with hashed password
     */
    public static function createCustomer(array $data): int
    {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        return self::create($data);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(array $customer, string $password): bool
    {
        return password_verify($password, $customer['password']);
    }

    /**
     * Get customer with addresses
     */
    public static function findWithAddresses(int $id): ?array
    {
        $customer = self::find($id);

        if (!$customer) {
            return null;
        }

        $sql = "SELECT * FROM `customer_addresses`
                WHERE customer_id = :customer_id
                ORDER BY is_default DESC, created_at DESC";

        $customer['addresses'] = self::query($sql, ['customer_id' => $id]);

        return $customer;
    }
}
