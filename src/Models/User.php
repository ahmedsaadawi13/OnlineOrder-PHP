<?php

namespace App\Models;

class User extends BaseModel
{
    protected static string $table = 'users';
    protected static bool $usesTenant = true;
    protected static bool $usesSoftDeletes = true;
    protected static array $fillable = [
        'tenant_id', 'role_id', 'first_name', 'last_name',
        'email', 'phone', 'password', 'avatar_url', 'is_active'
    ];

    /**
     * Find user by email and tenant
     */
    public static function findByEmail(string $email, ?int $tenantId = null): ?array
    {
        $sql = "SELECT * FROM `users` WHERE `email` = :email";
        $params = ['email' => $email];

        if ($tenantId) {
            $sql .= " AND `tenant_id` = :tenant_id";
            $params['tenant_id'] = $tenantId;
        }

        $sql .= " AND `deleted_at` IS NULL LIMIT 1";

        return self::queryOne($sql, $params);
    }

    /**
     * Find user with role
     */
    public static function findWithRole(int $id): ?array
    {
        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug
                FROM `users` u
                LEFT JOIN `roles` r ON u.role_id = r.id
                WHERE u.id = :id AND u.deleted_at IS NULL
                LIMIT 1";

        return self::queryOne($sql, ['id' => $id]);
    }

    /**
     * Create user with hashed password
     */
    public static function createUser(array $data): int
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
    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password']);
    }

    /**
     * Update last login
     */
    public static function updateLastLogin(int $id): void
    {
        self::update($id, ['last_login_at' => now()]);
    }
}
