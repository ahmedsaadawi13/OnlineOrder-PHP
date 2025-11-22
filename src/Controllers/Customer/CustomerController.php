<?php

namespace App\Controllers\Customer;

use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Core\Database;
use App\Validators\Validator;

class CustomerController
{
    private Response $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * Register new customer (guest checkout)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|alpha|min:2|max:100',
            'last_name' => 'required|alpha|min:2|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'required|phone',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
            'preferred_language' => 'in:en,ar'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        // Check if email already exists for this tenant
        $tenantId = $request->tenantId ?? ($_SESSION['tenant_id'] ?? null);
        if (!$tenantId) {
            return $this->response->error('Tenant context required', 400);
        }

        $existingCustomer = Customer::findByEmail($request->input('email'), $tenantId);
        if ($existingCustomer) {
            return $this->response->error('Email already registered', 422, [
                'email' => ['This email is already registered']
            ]);
        }

        try {
            $data = $request->only([
                'first_name', 'last_name', 'email', 'phone',
                'password', 'preferred_language'
            ]);
            $data['tenant_id'] = $tenantId;
            $data['is_active'] = true;

            $customerId = Customer::createCustomer($data);
            $customer = Customer::find($customerId);

            // Remove password from response
            unset($customer['password']);

            return $this->response->json($customer, 201, 'Customer registered successfully');

        } catch (\Exception $e) {
            logError('Failed to register customer: ' . $e->getMessage());
            return $this->response->error('Failed to register customer', 500);
        }
    }

    /**
     * Customer login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        $tenantId = $request->tenantId ?? ($_SESSION['tenant_id'] ?? null);
        if (!$tenantId) {
            return $this->response->error('Tenant context required', 400);
        }

        try {
            $customer = Customer::findByEmail($request->input('email'), $tenantId);

            if (!$customer || !Customer::verifyPassword($customer, $request->input('password'))) {
                return $this->response->error('Invalid credentials', 401);
            }

            if (!$customer['is_active']) {
                return $this->response->error('Account is inactive', 403);
            }

            // Update last login
            Customer::updateLastLogin($customer['id']);

            // Remove password from response
            unset($customer['password']);

            // Store customer ID in session
            $_SESSION['customer_id'] = $customer['id'];

            return $this->response->json([
                'customer' => $customer,
                'session_id' => session_id()
            ], 200, 'Login successful');

        } catch (\Exception $e) {
            logError('Customer login failed: ' . $e->getMessage());
            return $this->response->error('Login failed', 500);
        }
    }

    /**
     * Get current customer profile
     */
    public function me(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        try {
            $customer = Customer::findWithAddresses($customerId);

            if (!$customer) {
                return $this->response->error('Customer not found', 404);
            }

            // Remove password
            unset($customer['password']);

            // Add stats
            $customer['total_orders'] = Customer::getOrdersCount($customerId);
            $customer['total_spent'] = Customer::getTotalSpent($customerId);

            return $this->response->json($customer, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch customer: ' . $e->getMessage());
            return $this->response->error('Failed to fetch customer', 500);
        }
    }

    /**
     * Update customer profile
     */
    public function update(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'alpha|min:2|max:100',
            'last_name' => 'alpha|min:2|max:100',
            'phone' => 'phone',
            'avatar_url' => 'url',
            'date_of_birth' => 'date',
            'preferred_language' => 'in:en,ar'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->only([
                'first_name', 'last_name', 'phone', 'avatar_url',
                'date_of_birth', 'preferred_language'
            ]);

            Customer::update($customerId, $data);
            $customer = Customer::find($customerId);

            // Remove password
            unset($customer['password']);

            return $this->response->json($customer, 200, 'Profile updated successfully');

        } catch (\Exception $e) {
            logError('Failed to update customer: ' . $e->getMessage());
            return $this->response->error('Failed to update profile', 500);
        }
    }

    /**
     * Add customer address
     */
    public function addAddress(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'max:50',
            'address_line1' => 'required|max:255',
            'address_line2' => 'max:255',
            'city' => 'max:100',
            'state' => 'max:100',
            'postal_code' => 'max:20',
            'country' => 'max:100',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'delivery_instructions' => 'max:1000',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->only([
                'label', 'address_line1', 'address_line2', 'city', 'state',
                'postal_code', 'country', 'latitude', 'longitude',
                'delivery_instructions', 'is_default'
            ]);
            $data['customer_id'] = $customerId;

            // If setting as default, unset other defaults
            if ($data['is_default'] ?? false) {
                Database::query(
                    "UPDATE customer_addresses SET is_default = FALSE WHERE customer_id = :customer_id",
                    ['customer_id' => $customerId]
                );
            }

            $addressId = Database::insert('customer_addresses', $data);

            $address = Database::queryOne(
                "SELECT * FROM customer_addresses WHERE id = :id",
                ['id' => $addressId]
            );

            return $this->response->json($address, 201, 'Address added successfully');

        } catch (\Exception $e) {
            logError('Failed to add address: ' . $e->getMessage());
            return $this->response->error('Failed to add address', 500);
        }
    }

    /**
     * List customer addresses
     */
    public function listAddresses(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        try {
            $addresses = Database::query(
                "SELECT * FROM customer_addresses WHERE customer_id = :customer_id ORDER BY is_default DESC, created_at DESC",
                ['customer_id' => $customerId]
            );

            return $this->response->json($addresses, 200);

        } catch (\Exception $e) {
            logError('Failed to fetch addresses: ' . $e->getMessage());
            return $this->response->error('Failed to fetch addresses', 500);
        }
    }

    /**
     * Update address
     */
    public function updateAddress(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        $addressId = $request->param('id');

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        // Verify address belongs to customer
        $address = Database::queryOne(
            "SELECT * FROM customer_addresses WHERE id = :id AND customer_id = :customer_id",
            ['id' => $addressId, 'customer_id' => $customerId]
        );

        if (!$address) {
            return $this->response->error('Address not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'max:50',
            'address_line1' => 'max:255',
            'address_line2' => 'max:255',
            'city' => 'max:100',
            'state' => 'max:100',
            'postal_code' => 'max:20',
            'country' => 'max:100',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
            'delivery_instructions' => 'max:1000',
            'is_default' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->response->error('Validation failed', 422, $validator->errors());
        }

        try {
            $data = $request->only([
                'label', 'address_line1', 'address_line2', 'city', 'state',
                'postal_code', 'country', 'latitude', 'longitude',
                'delivery_instructions', 'is_default'
            ]);

            // If setting as default, unset other defaults
            if ($data['is_default'] ?? false) {
                Database::query(
                    "UPDATE customer_addresses SET is_default = FALSE WHERE customer_id = :customer_id",
                    ['customer_id' => $customerId]
                );
            }

            Database::update('customer_addresses', $data, 'id = :id', ['id' => $addressId]);

            $updatedAddress = Database::queryOne(
                "SELECT * FROM customer_addresses WHERE id = :id",
                ['id' => $addressId]
            );

            return $this->response->json($updatedAddress, 200, 'Address updated successfully');

        } catch (\Exception $e) {
            logError('Failed to update address: ' . $e->getMessage());
            return $this->response->error('Failed to update address', 500);
        }
    }

    /**
     * Delete address
     */
    public function deleteAddress(Request $request)
    {
        $customerId = $_SESSION['customer_id'] ?? null;
        $addressId = $request->param('id');

        if (!$customerId) {
            return $this->response->error('Not authenticated', 401);
        }

        // Verify address belongs to customer
        $address = Database::queryOne(
            "SELECT * FROM customer_addresses WHERE id = :id AND customer_id = :customer_id",
            ['id' => $addressId, 'customer_id' => $customerId]
        );

        if (!$address) {
            return $this->response->error('Address not found', 404);
        }

        try {
            Database::query(
                "DELETE FROM customer_addresses WHERE id = :id",
                ['id' => $addressId]
            );

            return $this->response->json(null, 200, 'Address deleted successfully');

        } catch (\Exception $e) {
            logError('Failed to delete address: ' . $e->getMessage());
            return $this->response->error('Failed to delete address', 500);
        }
    }

    /**
     * Customer logout
     */
    public function logout(Request $request)
    {
        unset($_SESSION['customer_id']);

        return $this->response->json(null, 200, 'Logout successful');
    }
}
