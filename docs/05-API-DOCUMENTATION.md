# 📡 Complete API Documentation

## **Restaurant Online Ordering SaaS Platform - REST API v1**

---

## **1. API Overview**

### **1.1 Base Information**

- **Base URL**: `https://api.yourapp.com/api/v1`
- **Protocol**: HTTPS only
- **Authentication**: JWT Bearer Token
- **Content-Type**: `application/json`
- **Character Encoding**: UTF-8

### **1.2 Response Format**

#### **Success Response**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Response data here
  },
  "meta": {
    "timestamp": "2025-11-19T10:30:00Z",
    "version": "1.0"
  }
}
```

#### **Error Response**
```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field_name": ["Validation error message"]
  },
  "meta": {
    "timestamp": "2025-11-19T10:30:00Z",
    "version": "1.0"
  }
}
```

### **1.3 HTTP Status Codes**

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 204 | No Content - Successful deletion |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error |

### **1.4 Authentication**

All authenticated requests must include JWT token in header:

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### **1.5 Pagination**

Paginated endpoints support these query parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

Response includes pagination metadata:

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  }
}
```

---

## **2. Authentication Endpoints**

### **2.1 Register Restaurant**

Register a new restaurant (tenant).

**Endpoint**: `POST /api/v1/auth/register`

**Access**: Public

**Request Body**:
```json
{
  "restaurant_name": "Awesome Restaurant",
  "email": "owner@awesome-restaurant.com",
  "phone": "+1234567890",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "first_name": "John",
  "last_name": "Doe",
  "currency": "USD",
  "timezone": "America/New_York"
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Restaurant registered successfully",
  "data": {
    "restaurant": {
      "id": 1,
      "name": "Awesome Restaurant",
      "slug": "awesome-restaurant",
      "email": "owner@awesome-restaurant.com",
      "status": "pending"
    },
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "owner@awesome-restaurant.com",
      "role": "restaurant_owner"
    },
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "8f7e6d5c4b3a2f1e0d9c8b7a6f5e4d3c...",
    "expires_in": 900
  }
}
```

---

### **2.2 Login**

Authenticate user and get tokens.

**Endpoint**: `POST /api/v1/auth/login`

**Access**: Public

**Request Body**:
```json
{
  "email": "owner@awesome-restaurant.com",
  "password": "SecurePass123!"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "owner@awesome-restaurant.com",
      "role": "restaurant_owner",
      "restaurant_id": 1
    },
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "8f7e6d5c4b3a2f1e0d9c8b7a6f5e4d3c...",
    "expires_in": 900
  }
}
```

---

### **2.3 Refresh Token**

Get new access token using refresh token.

**Endpoint**: `POST /api/v1/auth/refresh`

**Access**: Public

**Request Body**:
```json
{
  "refresh_token": "8f7e6d5c4b3a2f1e0d9c8b7a6f5e4d3c..."
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expires_in": 900
  }
}
```

---

### **2.4 Logout**

Revoke tokens and logout.

**Endpoint**: `POST /api/v1/auth/logout`

**Access**: Authenticated

**Response** (200):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### **2.5 Get Current User**

Get authenticated user details.

**Endpoint**: `GET /api/v1/auth/me`

**Access**: Authenticated

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "owner@awesome-restaurant.com",
    "role": {
      "id": 2,
      "name": "Restaurant Owner",
      "slug": "restaurant_owner"
    },
    "restaurant": {
      "id": 1,
      "name": "Awesome Restaurant",
      "slug": "awesome-restaurant",
      "status": "active"
    },
    "permissions": [
      "dashboard.view",
      "menu.manage",
      "orders.manage"
    ]
  }
}
```

---

### **2.6 Forgot Password**

Request password reset email.

**Endpoint**: `POST /api/v1/auth/forgot-password`

**Access**: Public

**Request Body**:
```json
{
  "email": "owner@awesome-restaurant.com"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

---

### **2.7 Reset Password**

Reset password using token from email.

**Endpoint**: `POST /api/v1/auth/reset-password`

**Access**: Public

**Request Body**:
```json
{
  "email": "owner@awesome-restaurant.com",
  "token": "abc123def456",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

## **3. Restaurant Management**

### **3.1 Get Restaurant**

Get current restaurant details.

**Endpoint**: `GET /api/v1/restaurants/{id}`

**Access**: Authenticated

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Awesome Restaurant",
    "slug": "awesome-restaurant",
    "email": "owner@awesome-restaurant.com",
    "phone": "+1234567890",
    "logo_url": "/uploads/logos/logo123.png",
    "currency": "USD",
    "timezone": "America/New_York",
    "status": "active",
    "subscription": {
      "plan": "Professional",
      "status": "active",
      "current_period_end": "2025-12-19"
    },
    "settings": {
      "theme_color": "#FF6B6B",
      "language": "en",
      "tax_rate": 8.5,
      "accepts_cash": true,
      "accepts_card": true,
      "stripe_enabled": true,
      "paypal_enabled": true
    }
  }
}
```

---

### **3.2 Update Restaurant**

Update restaurant information.

**Endpoint**: `PUT /api/v1/restaurants/{id}`

**Access**: Authenticated (restaurant_owner)

**Request Body**:
```json
{
  "name": "Awesome Restaurant & Bar",
  "phone": "+1234567890",
  "logo_url": "/uploads/logos/new-logo.png",
  "currency": "USD",
  "timezone": "America/New_York"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Restaurant updated successfully",
  "data": {
    // Updated restaurant object
  }
}
```

---

### **3.3 Update Settings**

Update restaurant settings.

**Endpoint**: `PUT /api/v1/restaurants/settings`

**Access**: Authenticated (restaurant_owner)

**Request Body**:
```json
{
  "theme_color": "#3498DB",
  "language": "en",
  "tax_rate": 10.0,
  "accepts_cash": true,
  "accepts_card": true,
  "stripe_enabled": true,
  "paypal_enabled": true,
  "email_notifications": true,
  "sms_notifications": true,
  "auto_accept_orders": false
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Settings updated successfully"
}
```

---

## **4. Branch Management**

### **4.1 List Branches**

Get all branches for restaurant.

**Endpoint**: `GET /api/v1/branches`

**Access**: Authenticated

**Query Parameters**:
- `is_active` - Filter by active status (true/false)

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Main Branch",
      "address_line1": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "country": "USA",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "phone": "+1234567890",
      "is_active": true,
      "accepts_online_orders": true,
      "opening_hours": [
        {
          "day_of_week": 1,
          "day_name": "Monday",
          "open_time": "10:00:00",
          "close_time": "22:00:00",
          "is_closed": false
        }
      ]
    }
  ]
}
```

---

### **4.2 Create Branch**

Create new branch.

**Endpoint**: `POST /api/v1/branches`

**Access**: Authenticated (restaurant_owner, branch_manager)

**Request Body**:
```json
{
  "name": "Downtown Branch",
  "address_line1": "456 Downtown Ave",
  "address_line2": "Suite 200",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "USA",
  "latitude": 40.7489,
  "longitude": -73.9680,
  "phone": "+1234567891",
  "email": "downtown@awesome-restaurant.com",
  "is_active": true,
  "accepts_online_orders": true
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Branch created successfully",
  "data": {
    "id": 2,
    // Branch object
  }
}
```

---

### **4.3 Update Branch**

Update branch details.

**Endpoint**: `PUT /api/v1/branches/{id}`

**Access**: Authenticated (restaurant_owner, branch_manager)

**Request Body**: Same as create

**Response** (200):
```json
{
  "success": true,
  "message": "Branch updated successfully",
  "data": {
    // Updated branch object
  }
}
```

---

### **4.4 Delete Branch**

Soft delete branch.

**Endpoint**: `DELETE /api/v1/branches/{id}`

**Access**: Authenticated (restaurant_owner)

**Response** (200):
```json
{
  "success": true,
  "message": "Branch deleted successfully"
}
```

---

### **4.5 Set Opening Hours**

Set opening hours for a branch.

**Endpoint**: `POST /api/v1/branches/{id}/opening-hours`

**Access**: Authenticated (restaurant_owner, branch_manager)

**Request Body**:
```json
{
  "opening_hours": [
    {
      "day_of_week": 0,
      "open_time": "10:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": 1,
      "open_time": "10:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": 6,
      "open_time": "12:00",
      "close_time": "20:00",
      "is_closed": false
    }
  ]
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Opening hours updated successfully"
}
```

---

## **5. Menu Management**

### **5.1 List Categories**

Get all menu categories.

**Endpoint**: `GET /api/v1/categories`

**Access**: Authenticated

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Burgers",
      "name_ar": "برغر",
      "slug": "burgers",
      "description": "Delicious burgers",
      "image_url": "/uploads/categories/burgers.jpg",
      "sort_order": 1,
      "is_active": true,
      "items_count": 12
    }
  ]
}
```

---

### **5.2 Create Category**

Create new menu category.

**Endpoint**: `POST /api/v1/categories`

**Access**: Authenticated (restaurant_owner, staff_admin)

**Request Body**:
```json
{
  "name": "Pizzas",
  "name_ar": "بيتزا",
  "description": "Italian style pizzas",
  "description_ar": "بيتزا إيطالية",
  "image_url": "/uploads/categories/pizzas.jpg",
  "sort_order": 2,
  "is_active": true
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 2,
    // Category object
  }
}
```

---

### **5.3 List Menu Items**

Get all menu items.

**Endpoint**: `GET /api/v1/menu-items`

**Access**: Authenticated

**Query Parameters**:
- `category_id` - Filter by category
- `is_available` - Filter by availability
- `is_featured` - Filter featured items
- `search` - Search by name/description

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "category_id": 1,
      "category_name": "Burgers",
      "name": "Classic Burger",
      "name_ar": "برغر كلاسيكي",
      "slug": "classic-burger",
      "description": "Juicy beef patty with cheese",
      "price": 12.99,
      "image_url": "/uploads/menu-items/burger1.jpg",
      "calories": 650,
      "preparation_time": 15,
      "is_available": true,
      "is_featured": true,
      "is_vegetarian": false,
      "is_vegan": false,
      "images": [
        "/uploads/menu-items/burger1.jpg",
        "/uploads/menu-items/burger2.jpg"
      ],
      "modifiers": [
        {
          "id": 1,
          "name": "Size",
          "type": "single",
          "is_required": true,
          "options": [
            {
              "id": 1,
              "name": "Regular",
              "price_modifier": 0.00,
              "is_default": true
            },
            {
              "id": 2,
              "name": "Large",
              "price_modifier": 3.00,
              "is_default": false
            }
          ]
        }
      ],
      "variants": [
        {
          "id": 1,
          "name": "With Cheese",
          "price_modifier": 2.00,
          "is_default": true
        }
      ]
    }
  ]
}
```

---

### **5.4 Create Menu Item**

Create new menu item.

**Endpoint**: `POST /api/v1/menu-items`

**Access**: Authenticated (restaurant_owner, staff_admin)

**Request Body**:
```json
{
  "category_id": 1,
  "name": "Spicy Chicken Burger",
  "name_ar": "برغر دجاج حار",
  "description": "Crispy chicken with spicy sauce",
  "description_ar": "دجاج مقرمش مع صوص حار",
  "price": 14.99,
  "image_url": "/uploads/menu-items/spicy-chicken.jpg",
  "calories": 720,
  "preparation_time": 18,
  "is_available": true,
  "is_featured": false,
  "is_vegetarian": false,
  "is_vegan": false,
  "is_gluten_free": false
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Menu item created successfully",
  "data": {
    "id": 15,
    // Menu item object
  }
}
```

---

### **5.5 Add Item Modifier**

Add modifier group to menu item.

**Endpoint**: `POST /api/v1/menu-items/{id}/modifiers`

**Access**: Authenticated (restaurant_owner, staff_admin)

**Request Body**:
```json
{
  "name": "Extra Toppings",
  "name_ar": "إضافات",
  "type": "multiple",
  "is_required": false,
  "min_selections": 0,
  "max_selections": 5,
  "options": [
    {
      "name": "Extra Cheese",
      "name_ar": "جبنة إضافية",
      "price_modifier": 2.00,
      "is_default": false
    },
    {
      "name": "Bacon",
      "name_ar": "بيكون",
      "price_modifier": 3.00,
      "is_default": false
    }
  ]
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Modifier added successfully",
  "data": {
    "id": 5,
    // Modifier object with options
  }
}
```

---

## **6. Order Management**

### **6.1 List Orders**

Get all orders for restaurant.

**Endpoint**: `GET /api/v1/orders`

**Access**: Authenticated

**Query Parameters**:
- `status` - Filter by status
- `branch_id` - Filter by branch
- `date_from` - Filter from date (Y-m-d)
- `date_to` - Filter to date (Y-m-d)
- `page` - Page number
- `per_page` - Items per page

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20251119-A1B2C3",
      "customer": {
        "id": 10,
        "name": "Jane Smith",
        "phone": "+1234567890",
        "email": "jane@example.com"
      },
      "branch": {
        "id": 1,
        "name": "Main Branch"
      },
      "status": "confirmed",
      "payment_status": "completed",
      "order_type": "delivery",
      "subtotal": 45.99,
      "tax_amount": 3.91,
      "delivery_fee": 5.00,
      "discount_amount": 5.00,
      "total_amount": 49.90,
      "items_count": 3,
      "created_at": "2025-11-19T10:30:00Z",
      "estimated_delivery_time": "2025-11-19T11:15:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10
  }
}
```

---

### **6.2 Get Order Details**

Get single order with full details.

**Endpoint**: `GET /api/v1/orders/{id}`

**Access**: Authenticated

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20251119-A1B2C3",
    "customer": {
      "id": 10,
      "first_name": "Jane",
      "last_name": "Smith",
      "phone": "+1234567890",
      "email": "jane@example.com"
    },
    "branch": {
      "id": 1,
      "name": "Main Branch",
      "phone": "+1234567890"
    },
    "status": "confirmed",
    "payment_status": "completed",
    "order_type": "delivery",
    "delivery_address": {
      "address_line1": "789 Customer St",
      "city": "New York",
      "latitude": 40.7580,
      "longitude": -73.9855
    },
    "items": [
      {
        "id": 1,
        "item_id": 5,
        "item_name": "Classic Burger",
        "quantity": 2,
        "unit_price": 12.99,
        "subtotal": 25.98,
        "selected_variant": {
          "name": "Large",
          "price_modifier": 3.00
        },
        "selected_modifiers": [
          {
            "name": "Extra Cheese",
            "price_modifier": 2.00
          }
        ],
        "special_instructions": "No onions"
      }
    ],
    "subtotal": 45.99,
    "tax_amount": 3.91,
    "delivery_fee": 5.00,
    "discount_amount": 5.00,
    "total_amount": 49.90,
    "coupon_code": "SAVE5",
    "payment_method": "stripe",
    "special_instructions": "Ring doorbell",
    "status_history": [
      {
        "status": "pending",
        "created_at": "2025-11-19T10:30:00Z"
      },
      {
        "status": "confirmed",
        "created_at": "2025-11-19T10:32:00Z",
        "notes": "Order accepted by restaurant"
      }
    ],
    "created_at": "2025-11-19T10:30:00Z",
    "scheduled_at": null,
    "estimated_delivery_time": "2025-11-19T11:15:00Z"
  }
}
```

---

### **6.3 Create Order (Customer)**

Customer creates new order.

**Endpoint**: `POST /api/v1/orders`

**Access**: Public or Authenticated (Customer)

**Request Body**:
```json
{
  "branch_id": 1,
  "order_type": "delivery",
  "customer": {
    "first_name": "Jane",
    "last_name": "Smith",
    "phone": "+1234567890",
    "email": "jane@example.com"
  },
  "delivery_address_id": 5,
  "items": [
    {
      "item_id": 5,
      "quantity": 2,
      "variant_id": 2,
      "modifiers": [
        {
          "modifier_id": 1,
          "option_id": 3
        }
      ],
      "special_instructions": "No onions"
    }
  ],
  "coupon_code": "SAVE5",
  "payment_method": "stripe",
  "payment_intent_id": "pi_xxxxxxxxxxxxx",
  "special_instructions": "Ring doorbell",
  "scheduled_at": null
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 150,
    "order_number": "ORD-20251119-D5E6F7",
    "total_amount": 49.90,
    "status": "pending",
    "payment_status": "processing",
    "estimated_delivery_time": "2025-11-19T11:45:00Z"
  }
}
```

---

### **6.4 Update Order Status**

Restaurant updates order status.

**Endpoint**: `PUT /api/v1/orders/{id}/status`

**Access**: Authenticated (restaurant staff)

**Request Body**:
```json
{
  "status": "preparing",
  "notes": "Started preparing order"
}
```

**Possible Status Values**:
- `confirmed` - Restaurant accepted
- `preparing` - Being prepared
- `ready_for_pickup` - Ready (takeout orders)
- `out_for_delivery` - Dispatched (delivery orders)
- `delivered` - Delivered to customer
- `completed` - Order completed
- `cancelled` - Order cancelled

**Response** (200):
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "status": "preparing",
    "updated_at": "2025-11-19T10:35:00Z"
  }
}
```

---

### **6.5 Cancel Order**

Cancel order (customer or restaurant).

**Endpoint**: `DELETE /api/v1/orders/{id}`

**Access**: Authenticated

**Request Body**:
```json
{
  "reason": "Customer requested cancellation"
}
```

**Response** (200):
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    "refund_initiated": true,
    "refund_amount": 49.90
  }
}
```

---

## **7. Customer APIs**

### **7.1 Customer Register**

Register new customer account.

**Endpoint**: `POST /api/v1/customers/register`

**Access**: Public

**Request Body**:
```json
{
  "restaurant_slug": "awesome-restaurant",
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "customer": {
      "id": 50,
      "first_name": "Jane",
      "last_name": "Smith",
      "email": "jane@example.com"
    },
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### **7.2 Customer Login**

Customer login.

**Endpoint**: `POST /api/v1/customers/login`

**Access**: Public

**Request Body**:
```json
{
  "restaurant_slug": "awesome-restaurant",
  "email": "jane@example.com",
  "password": "SecurePass123!"
}
```

**Response** (200): Same as register response

---

### **7.3 Get Customer Profile**

Get customer profile.

**Endpoint**: `GET /api/v1/customers/profile`

**Access**: Authenticated (Customer)

**Response** (200):
```json
{
  "success": true,
  "data": {
    "id": 50,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "preferred_language": "en",
    "addresses": [
      {
        "id": 5,
        "label": "Home",
        "address_line1": "789 Customer St",
        "city": "New York",
        "is_default": true
      }
    ],
    "orders_count": 25,
    "total_spent": 1245.50
  }
}
```

---

### **7.4 Add Customer Address**

Add delivery address.

**Endpoint**: `POST /api/v1/customers/addresses`

**Access**: Authenticated (Customer)

**Request Body**:
```json
{
  "label": "Work",
  "address_line1": "100 Office Building",
  "address_line2": "Floor 5",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "country": "USA",
  "latitude": 40.7589,
  "longitude": -73.9851,
  "delivery_instructions": "Security desk will call",
  "is_default": false
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Address added successfully",
  "data": {
    "id": 10,
    // Address object
  }
}
```

---

### **7.5 Get Order History**

Get customer's order history.

**Endpoint**: `GET /api/v1/customers/orders`

**Access**: Authenticated (Customer)

**Query Parameters**:
- `status` - Filter by status
- `page` - Page number

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20251119-A1B2C3",
      "status": "delivered",
      "total_amount": 49.90,
      "items_count": 3,
      "created_at": "2025-11-19T10:30:00Z",
      "delivered_at": "2025-11-19T11:20:00Z"
    }
  ]
}
```

---

## **8. Payment APIs**

### **8.1 Create Payment Intent (Stripe)**

Create Stripe payment intent for order.

**Endpoint**: `POST /api/v1/payments/stripe/intent`

**Access**: Public or Authenticated (Customer)

**Request Body**:
```json
{
  "amount": 49.90,
  "currency": "USD",
  "order_id": 150
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "client_secret": "pi_3xxxxxxxxxxxxx_secret_yyyyyyyyyyyyyy",
    "payment_intent_id": "pi_3xxxxxxxxxxxxx",
    "publishable_key": "pk_test_xxxxxxxxxxxx"
  }
}
```

---

### **8.2 Stripe Webhook**

Handle Stripe webhooks.

**Endpoint**: `POST /api/v1/webhooks/stripe`

**Access**: Public (Verified by Stripe signature)

**Headers**:
- `Stripe-Signature` - Webhook signature

**Response** (200):
```json
{
  "success": true,
  "message": "Webhook processed"
}
```

---

## **9. Coupon APIs**

### **9.1 List Coupons**

Get all coupons.

**Endpoint**: `GET /api/v1/coupons`

**Access**: Authenticated (restaurant staff)

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "SAVE5",
      "description": "Get $5 off on orders over $30",
      "type": "fixed",
      "value": 5.00,
      "min_order_value": 30.00,
      "max_uses": 100,
      "max_uses_per_user": 1,
      "times_used": 45,
      "starts_at": "2025-11-01T00:00:00Z",
      "expires_at": "2025-11-30T23:59:59Z",
      "is_active": true
    }
  ]
}
```

---

### **9.2 Create Coupon**

Create new coupon.

**Endpoint**: `POST /api/v1/coupons`

**Access**: Authenticated (restaurant_owner, staff_admin)

**Request Body**:
```json
{
  "code": "WELCOME10",
  "description": "10% off for new customers",
  "description_ar": "خصم 10% للعملاء الجدد",
  "type": "percentage",
  "value": 10.00,
  "min_order_value": 20.00,
  "max_discount_amount": 15.00,
  "max_uses": 500,
  "max_uses_per_user": 1,
  "applicable_to": "all",
  "starts_at": "2025-11-19T00:00:00Z",
  "expires_at": "2025-12-31T23:59:59Z",
  "is_active": true
}
```

**Response** (201):
```json
{
  "success": true,
  "message": "Coupon created successfully",
  "data": {
    "id": 5,
    // Coupon object
  }
}
```

---

### **9.3 Validate Coupon**

Validate coupon code for order.

**Endpoint**: `POST /api/v1/coupons/validate`

**Access**: Public

**Request Body**:
```json
{
  "code": "SAVE5",
  "order_total": 45.99,
  "customer_id": 50
}
```

**Response** (200):
```json
{
  "success": true,
  "data": {
    "valid": true,
    "discount_amount": 5.00,
    "final_total": 40.99,
    "coupon": {
      "id": 1,
      "code": "SAVE5",
      "description": "Get $5 off on orders over $30"
    }
  }
}
```

---

## **10. Super Admin APIs**

### **10.1 List All Tenants**

Get all registered restaurants.

**Endpoint**: `GET /api/v1/admin/tenants`

**Access**: Authenticated (super_admin)

**Query Parameters**:
- `status` - Filter by status
- `page` - Page number

**Response** (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Awesome Restaurant",
      "slug": "awesome-restaurant",
      "email": "owner@awesome-restaurant.com",
      "status": "active",
      "subscription": {
        "plan": "Professional",
        "status": "active"
      },
      "branches_count": 3,
      "orders_count": 1250,
      "total_revenue": 45678.90,
      "created_at": "2025-01-15T10:00:00Z"
    }
  ]
}
```

---

### **10.2 Get Analytics**

Get platform-wide analytics.

**Endpoint**: `GET /api/v1/admin/analytics`

**Access**: Authenticated (super_admin)

**Query Parameters**:
- `date_from` - Start date
- `date_to` - End date

**Response** (200):
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_tenants": 150,
      "active_tenants": 135,
      "total_orders": 45678,
      "total_revenue": 1234567.89,
      "total_customers": 12345
    },
    "revenue_by_month": [
      {
        "month": "2025-01",
        "revenue": 98765.43
      }
    ],
    "top_restaurants": [
      {
        "id": 1,
        "name": "Awesome Restaurant",
        "orders_count": 2345,
        "revenue": 67890.12
      }
    ]
  }
}
```

---

## **11. Dashboard APIs**

### **11.1 Get Dashboard Stats**

Get dashboard statistics.

**Endpoint**: `GET /api/v1/dashboard/stats`

**Access**: Authenticated

**Query Parameters**:
- `period` - today, week, month, year

**Response** (200):
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_orders": 156,
      "total_revenue": 6789.45,
      "pending_orders": 8,
      "completed_orders": 145,
      "cancelled_orders": 3
    },
    "revenue_chart": [
      {
        "date": "2025-11-19",
        "revenue": 678.90,
        "orders": 23
      }
    ],
    "popular_items": [
      {
        "id": 5,
        "name": "Classic Burger",
        "orders_count": 67,
        "revenue": 869.33
      }
    ],
    "recent_orders": [
      {
        "id": 1,
        "order_number": "ORD-20251119-A1B2C3",
        "customer_name": "Jane Smith",
        "total": 49.90,
        "status": "confirmed",
        "created_at": "2025-11-19T10:30:00Z"
      }
    ]
  }
}
```

---

## **12. Public Menu API (No Auth)**

### **12.1 Get Restaurant Menu**

Get public menu for restaurant.

**Endpoint**: `GET /api/v1/restaurants/{slug}/menu`

**Access**: Public

**Query Parameters**:
- `branch_id` - Filter by branch availability

**Response** (200):
```json
{
  "success": true,
  "data": {
    "restaurant": {
      "id": 1,
      "name": "Awesome Restaurant",
      "logo_url": "/uploads/logos/logo.png",
      "theme_color": "#FF6B6B"
    },
    "categories": [
      {
        "id": 1,
        "name": "Burgers",
        "items": [
          {
            "id": 5,
            "name": "Classic Burger",
            "description": "Juicy beef patty",
            "price": 12.99,
            "image_url": "/uploads/items/burger.jpg",
            "is_available": true
          }
        ]
      }
    ]
  }
}
```

---

## **13. Rate Limiting**

All authenticated endpoints are rate limited:
- **Default**: 60 requests per minute per user
- **Auth endpoints**: 10 requests per minute per IP

When rate limit exceeded, API returns:

```json
{
  "success": false,
  "message": "Too many requests",
  "errors": {
    "rate_limit": "Rate limit exceeded. Try again in 45 seconds."
  }
}
```

**Headers**:
- `X-RateLimit-Limit`: 60
- `X-RateLimit-Remaining`: 0
- `X-RateLimit-Reset`: 1700485200

---

## **14. Error Codes Reference**

| Code | Message | Description |
|------|---------|-------------|
| `AUTH_001` | Invalid credentials | Email/password incorrect |
| `AUTH_002` | Token expired | JWT token expired, use refresh token |
| `AUTH_003` | Invalid token | Malformed or invalid JWT |
| `AUTH_004` | Unauthorized | Missing authentication |
| `PERM_001` | Insufficient permissions | User lacks required permission |
| `VAL_001` | Validation failed | Input validation errors |
| `RES_001` | Resource not found | Requested resource doesn't exist |
| `RES_002` | Resource already exists | Duplicate resource |
| `PAY_001` | Payment failed | Payment processing error |
| `PAY_002` | Invalid payment method | Unsupported payment method |
| `ORD_001` | Invalid order | Order validation failed |
| `ORD_002` | Order cannot be modified | Order in non-editable state |

---

**API Version**: 1.0
**Last Updated**: 2025-11-19
**Total Endpoints**: 50+

