# 🧪 API Testing Guide

## **Restaurant Online Ordering SaaS Platform - Testing Documentation**

---

## **Table of Contents**

1. [Quick Start](#quick-start)
2. [Authentication Flow](#authentication-flow)
3. [Complete API Examples](#complete-api-examples)
4. [Postman Collection](#postman-collection)
5. [Testing Checklist](#testing-checklist)
6. [Common Issues](#common-issues)

---

## **1. Quick Start**

### **Base URL**
```
Local: http://localhost/restaurant-saas/public/api/v1
Production: https://yourdomain.com/api/v1
```

### **Required Headers**
```http
Content-Type: application/json
Authorization: Bearer {access_token}  # For protected endpoints
```

---

## **2. Authentication Flow**

### **Step 1: Register New Restaurant**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_name": "Pizza Palace",
    "email": "owner@pizzapalace.com",
    "phone": "+1234567890",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe",
    "currency": "USD",
    "timezone": "America/New_York"
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Restaurant registered successfully",
  "data": {
    "restaurant": {
      "id": 1,
      "name": "Pizza Palace",
      "slug": "pizza-palace",
      "email": "owner@pizzapalace.com",
      "status": "active"
    },
    "user": {
      "id": 1,
      "tenant_id": 1,
      "role_id": 2,
      "name": "John Doe",
      "email": "owner@pizzapalace.com"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "refresh_token": "a1b2c3d4e5f6...",
      "expires_in": 900
    }
  }
}
```

### **Step 2: Login**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "owner@pizzapalace.com",
    "password": "SecurePass123!"
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "tenant_id": 1,
      "name": "John Doe",
      "email": "owner@pizzapalace.com",
      "role": "Restaurant Owner"
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
      "refresh_token": "a1b2c3d4e5f6...",
      "expires_in": 900
    }
  }
}
```

### **Step 3: Get Current User**

```bash
curl -X GET http://localhost/restaurant-saas/public/api/v1/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "tenant_id": 1,
    "role_id": 2,
    "name": "John Doe",
    "email": "owner@pizzapalace.com",
    "role": "Restaurant Owner"
  }
}
```

### **Step 4: Refresh Token**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "a1b2c3d4e5f6..."
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 900
  }
}
```

### **Step 5: Logout**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/auth/logout \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "a1b2c3d4e5f6..."
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## **3. Complete API Examples**

### **Categories Management**

#### **Create Category**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/categories \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pizzas",
    "name_ar": "بيتزا",
    "description": "Delicious wood-fired pizzas",
    "display_order": 1,
    "is_active": true
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "name": "Pizzas",
    "name_ar": "بيتزا",
    "slug": "pizzas",
    "description": "Delicious wood-fired pizzas",
    "display_order": 1,
    "is_active": true,
    "created_at": "2025-11-19 10:30:00"
  }
}
```

#### **List All Categories**

```bash
curl -X GET http://localhost/restaurant-saas/public/api/v1/categories \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pizzas",
      "slug": "pizzas",
      "item_count": 12,
      "is_active": true
    },
    {
      "id": 2,
      "name": "Burgers",
      "slug": "burgers",
      "item_count": 8,
      "is_active": true
    }
  ]
}
```

#### **Get Category Details**

```bash
curl -X GET http://localhost/restaurant-saas/public/api/v1/categories/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

#### **Update Category**

```bash
curl -X PUT http://localhost/restaurant-saas/public/api/v1/categories/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Specialty Pizzas",
    "description": "Our signature wood-fired pizzas",
    "display_order": 1
  }'
```

#### **Delete Category**

```bash
curl -X DELETE http://localhost/restaurant-saas/public/api/v1/categories/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

---

### **Menu Items Management**

#### **Create Menu Item**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/menu-items \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "category_id": 1,
    "name": "Margherita Pizza",
    "name_ar": "بيتزا مارجريتا",
    "description": "Classic pizza with tomato, mozzarella, and basil",
    "description_ar": "بيتزا كلاسيكية مع الطماطم والموزاريلا والريحان",
    "price": 12.99,
    "cost": 5.50,
    "image_url": "https://example.com/images/margherita.jpg",
    "preparation_time": 15,
    "calories": 800,
    "is_vegetarian": true,
    "is_vegan": false,
    "is_spicy": false,
    "is_available": true,
    "is_featured": true
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Menu item created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "category_id": 1,
    "name": "Margherita Pizza",
    "slug": "margherita-pizza",
    "price": 12.99,
    "is_available": true,
    "created_at": "2025-11-19 10:35:00"
  }
}
```

#### **List Menu Items**

```bash
# All items
curl -X GET http://localhost/restaurant-saas/public/api/v1/menu-items \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Filter by category
curl -X GET "http://localhost/restaurant-saas/public/api/v1/menu-items?category_id=1" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Search by name
curl -X GET "http://localhost/restaurant-saas/public/api/v1/menu-items?search=pizza" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Filter by availability
curl -X GET "http://localhost/restaurant-saas/public/api/v1/menu-items?is_available=1" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

#### **Get Menu Item with Modifiers**

```bash
curl -X GET http://localhost/restaurant-saas/public/api/v1/menu-items/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Margherita Pizza",
    "description": "Classic pizza with tomato, mozzarella, and basil",
    "price": 12.99,
    "category": {
      "id": 1,
      "name": "Pizzas"
    },
    "modifiers": [
      {
        "id": 1,
        "name": "Size",
        "is_required": true,
        "min_selections": 1,
        "max_selections": 1,
        "options": [
          {
            "id": 1,
            "name": "Small (10\")",
            "price_adjustment": 0.00
          },
          {
            "id": 2,
            "name": "Medium (12\")",
            "price_adjustment": 3.00
          },
          {
            "id": 3,
            "name": "Large (14\")",
            "price_adjustment": 5.00
          }
        ]
      },
      {
        "id": 2,
        "name": "Extra Toppings",
        "is_required": false,
        "max_selections": 5,
        "options": [
          {
            "id": 4,
            "name": "Mushrooms",
            "price_adjustment": 1.50
          },
          {
            "id": 5,
            "name": "Pepperoni",
            "price_adjustment": 2.00
          }
        ]
      }
    ]
  }
}
```

#### **Update Menu Item**

```bash
curl -X PUT http://localhost/restaurant-saas/public/api/v1/menu-items/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "price": 13.99,
    "is_featured": true,
    "is_available": true
  }'
```

#### **Delete Menu Item**

```bash
curl -X DELETE http://localhost/restaurant-saas/public/api/v1/menu-items/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

### **Orders Management**

#### **Create Order**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/orders \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "branch_id": 1,
    "order_type": "delivery",
    "delivery_address_id": 1,
    "coupon_code": "SAVE10",
    "special_instructions": "Extra napkins please",
    "items": [
      {
        "menu_item_id": 1,
        "quantity": 2,
        "unit_price": 12.99,
        "special_instructions": "No basil",
        "modifiers": [
          {
            "modifier_option_id": 2,
            "price_adjustment": 3.00
          },
          {
            "modifier_option_id": 4,
            "price_adjustment": 1.50
          }
        ]
      },
      {
        "menu_item_id": 5,
        "quantity": 1,
        "unit_price": 8.99
      }
    ]
  }'
```

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20251119-A1B2C3",
    "tenant_id": 1,
    "customer_id": 1,
    "branch_id": 1,
    "order_type": "delivery",
    "status": "pending",
    "subtotal": 43.95,
    "tax_amount": 3.52,
    "delivery_fee": 5.00,
    "coupon_discount": 4.40,
    "total_amount": 48.07,
    "created_at": "2025-11-19 11:00:00"
  }
}
```

#### **List Orders**

```bash
# All orders
curl -X GET http://localhost/restaurant-saas/public/api/v1/orders \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Filter by status
curl -X GET "http://localhost/restaurant-saas/public/api/v1/orders?status=pending" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Filter by date range
curl -X GET "http://localhost/restaurant-saas/public/api/v1/orders?start_date=2025-11-01&end_date=2025-11-30" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Pagination
curl -X GET "http://localhost/restaurant-saas/public/api/v1/orders?page=1&per_page=20" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20251119-A1B2C3",
      "customer_name": "Jane Smith",
      "status": "pending",
      "total_amount": 48.07,
      "created_at": "2025-11-19 11:00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "total_pages": 1
  }
}
```

#### **Get Order Details**

```bash
curl -X GET http://localhost/restaurant-saas/public/api/v1/orders/1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20251119-A1B2C3",
    "status": "pending",
    "customer": {
      "id": 1,
      "name": "Jane Smith",
      "phone": "+1234567890",
      "email": "jane@example.com"
    },
    "branch": {
      "id": 1,
      "name": "Downtown Branch",
      "phone": "+1987654321"
    },
    "items": [
      {
        "id": 1,
        "menu_item_name": "Margherita Pizza",
        "quantity": 2,
        "unit_price": 12.99,
        "subtotal": 35.96,
        "modifiers": [
          {
            "option_name": "Medium (12\")",
            "price_adjustment": 3.00
          },
          {
            "option_name": "Mushrooms",
            "price_adjustment": 1.50
          }
        ]
      }
    ],
    "subtotal": 43.95,
    "tax_amount": 3.52,
    "delivery_fee": 5.00,
    "coupon_discount": 4.40,
    "total_amount": 48.07,
    "status_history": [
      {
        "status": "pending",
        "changed_at": "2025-11-19 11:00:00",
        "changed_by": "John Doe"
      }
    ],
    "created_at": "2025-11-19 11:00:00"
  }
}
```

#### **Update Order Status**

```bash
curl -X PATCH http://localhost/restaurant-saas/public/api/v1/orders/1/status \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "status": "confirmed",
    "notes": "Order confirmed by kitchen"
  }'
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20251119-A1B2C3",
    "status": "confirmed",
    "updated_at": "2025-11-19 11:15:00"
  }
}
```

**Valid status transitions:**
- `pending` → `confirmed` → `preparing` → `ready` → `out_for_delivery` → `delivered` → `completed`
- `pending` → `cancelled`
- `confirmed` → `cancelled`

#### **Cancel Order**

```bash
curl -X POST http://localhost/restaurant-saas/public/api/v1/orders/1/cancel \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Customer requested cancellation"
  }'
```

---

## **4. Postman Collection**

### **Import This JSON into Postman**

```json
{
  "info": {
    "name": "Restaurant SaaS API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{access_token}}",
        "type": "string"
      }
    ]
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost/restaurant-saas/public/api/v1"
    },
    {
      "key": "access_token",
      "value": ""
    },
    {
      "key": "refresh_token",
      "value": ""
    }
  ],
  "item": [
    {
      "name": "Auth",
      "item": [
        {
          "name": "Register",
          "event": [
            {
              "listen": "test",
              "script": {
                "exec": [
                  "if (pm.response.code === 200 || pm.response.code === 201) {",
                  "    var jsonData = pm.response.json();",
                  "    pm.collectionVariables.set('access_token', jsonData.data.tokens.access_token);",
                  "    pm.collectionVariables.set('refresh_token', jsonData.data.tokens.refresh_token);",
                  "}"
                ]
              }
            }
          ],
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"restaurant_name\": \"Pizza Palace\",\n  \"email\": \"owner@pizzapalace.com\",\n  \"phone\": \"+1234567890\",\n  \"password\": \"SecurePass123!\",\n  \"password_confirmation\": \"SecurePass123!\",\n  \"first_name\": \"John\",\n  \"last_name\": \"Doe\",\n  \"currency\": \"USD\",\n  \"timezone\": \"America/New_York\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/auth/register",
              "host": ["{{base_url}}"],
              "path": ["auth", "register"]
            }
          }
        },
        {
          "name": "Login",
          "event": [
            {
              "listen": "test",
              "script": {
                "exec": [
                  "if (pm.response.code === 200) {",
                  "    var jsonData = pm.response.json();",
                  "    pm.collectionVariables.set('access_token', jsonData.data.tokens.access_token);",
                  "    pm.collectionVariables.set('refresh_token', jsonData.data.tokens.refresh_token);",
                  "}"
                ]
              }
            }
          ],
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"email\": \"owner@pizzapalace.com\",\n  \"password\": \"SecurePass123!\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/auth/login",
              "host": ["{{base_url}}"],
              "path": ["auth", "login"]
            }
          }
        },
        {
          "name": "Get Current User",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/auth/me",
              "host": ["{{base_url}}"],
              "path": ["auth", "me"]
            }
          }
        },
        {
          "name": "Refresh Token",
          "event": [
            {
              "listen": "test",
              "script": {
                "exec": [
                  "if (pm.response.code === 200) {",
                  "    var jsonData = pm.response.json();",
                  "    pm.collectionVariables.set('access_token', jsonData.data.access_token);",
                  "}"
                ]
              }
            }
          ],
          "request": {
            "auth": {
              "type": "noauth"
            },
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"refresh_token\": \"{{refresh_token}}\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/auth/refresh",
              "host": ["{{base_url}}"],
              "path": ["auth", "refresh"]
            }
          }
        },
        {
          "name": "Logout",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"refresh_token\": \"{{refresh_token}}\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/auth/logout",
              "host": ["{{base_url}}"],
              "path": ["auth", "logout"]
            }
          }
        }
      ]
    },
    {
      "name": "Categories",
      "item": [
        {
          "name": "List Categories",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/categories",
              "host": ["{{base_url}}"],
              "path": ["categories"]
            }
          }
        },
        {
          "name": "Create Category",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Pizzas\",\n  \"name_ar\": \"بيتزا\",\n  \"description\": \"Delicious wood-fired pizzas\",\n  \"display_order\": 1,\n  \"is_active\": true\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/categories",
              "host": ["{{base_url}}"],
              "path": ["categories"]
            }
          }
        },
        {
          "name": "Get Category",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/categories/:id",
              "host": ["{{base_url}}"],
              "path": ["categories", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Update Category",
          "request": {
            "method": "PUT",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"Specialty Pizzas\",\n  \"description\": \"Our signature wood-fired pizzas\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/categories/:id",
              "host": ["{{base_url}}"],
              "path": ["categories", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Delete Category",
          "request": {
            "method": "DELETE",
            "header": [],
            "url": {
              "raw": "{{base_url}}/categories/:id",
              "host": ["{{base_url}}"],
              "path": ["categories", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        }
      ]
    },
    {
      "name": "Menu Items",
      "item": [
        {
          "name": "List Menu Items",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/menu-items?category_id=&search=&is_available=",
              "host": ["{{base_url}}"],
              "path": ["menu-items"],
              "query": [
                {
                  "key": "category_id",
                  "value": ""
                },
                {
                  "key": "search",
                  "value": ""
                },
                {
                  "key": "is_available",
                  "value": ""
                }
              ]
            }
          }
        },
        {
          "name": "Create Menu Item",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"category_id\": 1,\n  \"name\": \"Margherita Pizza\",\n  \"name_ar\": \"بيتزا مارجريتا\",\n  \"description\": \"Classic pizza with tomato, mozzarella, and basil\",\n  \"price\": 12.99,\n  \"cost\": 5.50,\n  \"preparation_time\": 15,\n  \"calories\": 800,\n  \"is_vegetarian\": true,\n  \"is_available\": true,\n  \"is_featured\": true\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/menu-items",
              "host": ["{{base_url}}"],
              "path": ["menu-items"]
            }
          }
        },
        {
          "name": "Get Menu Item",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/menu-items/:id",
              "host": ["{{base_url}}"],
              "path": ["menu-items", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Update Menu Item",
          "request": {
            "method": "PUT",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"price\": 13.99,\n  \"is_featured\": true\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/menu-items/:id",
              "host": ["{{base_url}}"],
              "path": ["menu-items", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Delete Menu Item",
          "request": {
            "method": "DELETE",
            "header": [],
            "url": {
              "raw": "{{base_url}}/menu-items/:id",
              "host": ["{{base_url}}"],
              "path": ["menu-items", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        }
      ]
    },
    {
      "name": "Orders",
      "item": [
        {
          "name": "List Orders",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/orders?status=&start_date=&end_date=&page=1&per_page=20",
              "host": ["{{base_url}}"],
              "path": ["orders"],
              "query": [
                {
                  "key": "status",
                  "value": ""
                },
                {
                  "key": "start_date",
                  "value": ""
                },
                {
                  "key": "end_date",
                  "value": ""
                },
                {
                  "key": "page",
                  "value": "1"
                },
                {
                  "key": "per_page",
                  "value": "20"
                }
              ]
            }
          }
        },
        {
          "name": "Create Order",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"customer_id\": 1,\n  \"branch_id\": 1,\n  \"order_type\": \"delivery\",\n  \"delivery_address_id\": 1,\n  \"special_instructions\": \"Extra napkins please\",\n  \"items\": [\n    {\n      \"menu_item_id\": 1,\n      \"quantity\": 2,\n      \"unit_price\": 12.99,\n      \"modifiers\": [\n        {\n          \"modifier_option_id\": 2,\n          \"price_adjustment\": 3.00\n        }\n      ]\n    }\n  ]\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/orders",
              "host": ["{{base_url}}"],
              "path": ["orders"]
            }
          }
        },
        {
          "name": "Get Order",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/orders/:id",
              "host": ["{{base_url}}"],
              "path": ["orders", ":id"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Update Order Status",
          "request": {
            "method": "PATCH",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"status\": \"confirmed\",\n  \"notes\": \"Order confirmed by kitchen\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/orders/:id/status",
              "host": ["{{base_url}}"],
              "path": ["orders", ":id", "status"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        },
        {
          "name": "Cancel Order",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"reason\": \"Customer requested cancellation\"\n}",
              "options": {
                "raw": {
                  "language": "json"
                }
              }
            },
            "url": {
              "raw": "{{base_url}}/orders/:id/cancel",
              "host": ["{{base_url}}"],
              "path": ["orders", ":id", "cancel"],
              "variable": [
                {
                  "key": "id",
                  "value": "1"
                }
              ]
            }
          }
        }
      ]
    }
  ]
}
```

---

## **5. Testing Checklist**

### **Authentication Testing**
- [ ] Register new restaurant with valid data
- [ ] Register with duplicate email (should fail)
- [ ] Register with weak password (should fail)
- [ ] Login with correct credentials
- [ ] Login with wrong password (should fail)
- [ ] Access protected endpoint without token (should return 401)
- [ ] Access protected endpoint with valid token
- [ ] Access protected endpoint with expired token (should return 401)
- [ ] Refresh token successfully
- [ ] Refresh with invalid token (should fail)
- [ ] Logout successfully
- [ ] Use token after logout (should fail)

### **Multi-Tenant Isolation Testing**
- [ ] Create resources as Restaurant A
- [ ] Create resources as Restaurant B
- [ ] Verify Restaurant A cannot see Restaurant B's data
- [ ] Verify Restaurant B cannot see Restaurant A's data
- [ ] Try accessing another tenant's resource by ID (should return 404)

### **Rate Limiting Testing**
- [ ] Make 60 requests within 1 minute (should succeed)
- [ ] Make 61st request (should return 429)
- [ ] Check X-RateLimit-* headers
- [ ] Wait for window to reset and retry

### **Input Validation Testing**
- [ ] Submit form with missing required fields
- [ ] Submit invalid email format
- [ ] Submit password without confirmation
- [ ] Submit non-numeric value for price field
- [ ] Submit SQL injection attempt in text field
- [ ] Submit XSS script in text field

### **CRUD Operations Testing**
For each resource (Categories, Menu Items, Orders):
- [ ] Create new resource
- [ ] List all resources
- [ ] Get single resource
- [ ] Update resource
- [ ] Delete resource
- [ ] Get deleted resource (should return 404)

### **Order Flow Testing**
- [ ] Create order with valid items
- [ ] Create order with invalid item_id (should fail)
- [ ] Update order status: pending → confirmed
- [ ] Update order status: confirmed → preparing
- [ ] Update order status: preparing → ready
- [ ] Update order status: ready → out_for_delivery
- [ ] Update order status: out_for_delivery → delivered
- [ ] Try invalid status transition (should fail)
- [ ] Cancel pending order
- [ ] Try to cancel completed order (should fail)

### **Security Testing**
- [ ] Verify HTTPS redirect (production only)
- [ ] Verify secure headers (X-Frame-Options, X-XSS-Protection, etc.)
- [ ] Verify CORS headers
- [ ] Test SQL injection on all input fields
- [ ] Test XSS on all text fields
- [ ] Verify passwords are hashed (check database)
- [ ] Verify tokens are signed and validated

---

## **6. Common Issues**

### **401 Unauthorized**
**Cause:** Missing or invalid JWT token
**Solution:**
```bash
# Ensure Authorization header is present
-H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# If token expired, use refresh endpoint
curl -X POST http://localhost/restaurant-saas/public/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "YOUR_REFRESH_TOKEN"}'
```

### **422 Validation Error**
**Cause:** Input validation failed
**Solution:** Check the `errors` object in response:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required", "The email must be a valid email address"],
    "password": ["The password must be at least 8 characters"]
  }
}
```

### **429 Too Many Requests**
**Cause:** Rate limit exceeded
**Solution:** Wait for the time specified in `Retry-After` header (in seconds)

### **404 Not Found**
**Cause:** Resource doesn't exist or doesn't belong to your tenant
**Solution:**
- Verify resource ID is correct
- Ensure you're authenticated as the correct tenant
- Check if resource was soft-deleted

### **500 Internal Server Error**
**Cause:** Server-side error
**Solution:**
- Check server logs: `storage/logs/error.log`
- Check Apache error log: `/var/log/apache2/error.log`
- Ensure database connection is working
- Verify all required environment variables are set

### **CORS Error (in browser)**
**Cause:** CORS headers not configured
**Solution:** Update `.env`:
```
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

---

## **7. Performance Testing**

### **Load Testing with Apache Bench**

```bash
# Test login endpoint (100 requests, 10 concurrent)
ab -n 100 -c 10 -p login.json -T application/json \
  http://localhost/restaurant-saas/public/api/v1/auth/login

# Where login.json contains:
# {"email":"owner@pizzapalace.com","password":"SecurePass123!"}
```

### **Expected Metrics**
- **Response Time:** < 200ms (p95)
- **Throughput:** > 50 requests/second
- **Error Rate:** < 1%

---

**Last Updated:** 2025-11-19
**Version:** 1.0
**API Version:** v1
