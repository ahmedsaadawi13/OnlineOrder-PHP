# 🏗️ Backend Folder Structure & Organization

## **Restaurant Online Ordering SaaS Platform - PHP Backend**

---

## **1. Complete Directory Structure**

```
OnlineOrder-PHP/
├── config/                          # Configuration files
│   ├── app.php                      # Application configuration
│   ├── database.php                 # Database configuration
│   ├── cache.php                    # Redis cache configuration
│   ├── mail.php                     # Email configuration
│   ├── sms.php                      # SMS configuration
│   ├── payment.php                  # Payment gateways configuration
│   └── cors.php                     # CORS settings
│
├── database/                        # Database related files
│   ├── migrations/                  # SQL migration files
│   │   ├── 001_create_restaurants_table.sql
│   │   ├── 002_create_users_table.sql
│   │   └── ...
│   └── seeds/                       # Database seeders
│       ├── RolesSeeder.php
│       ├── PermissionsSeeder.php
│       └── DemoDataSeeder.php
│
├── public/                          # Public web root (Apache/Nginx points here)
│   ├── index.php                    # Application entry point
│   ├── .htaccess                    # Apache rewrite rules
│   ├── assets/                      # Static assets
│   │   ├── css/                     # Stylesheets
│   │   ├── js/                      # JavaScript files
│   │   └── images/                  # Static images
│   └── uploads/                     # Publicly accessible uploads
│       ├── menu-items/              # Menu item images
│       ├── logos/                   # Restaurant logos
│       └── qr-codes/                # QR code images
│
├── src/                             # Application source code
│   ├── Controllers/                 # HTTP Controllers
│   │   ├── Auth/
│   │   │   ├── AuthController.php
│   │   │   ├── RegisterController.php
│   │   │   └── PasswordResetController.php
│   │   ├── Restaurant/
│   │   │   ├── RestaurantController.php
│   │   │   ├── BranchController.php
│   │   │   ├── SettingsController.php
│   │   │   └── StaffController.php
│   │   ├── Menu/
│   │   │   ├── CategoryController.php
│   │   │   ├── MenuItemController.php
│   │   │   ├── ModifierController.php
│   │   │   └── VariantController.php
│   │   ├── Order/
│   │   │   ├── OrderController.php
│   │   │   ├── OrderTrackingController.php
│   │   │   └── OrderStatusController.php
│   │   ├── Customer/
│   │   │   ├── CustomerController.php
│   │   │   ├── AddressController.php
│   │   │   └── FavoriteController.php
│   │   ├── Payment/
│   │   │   ├── PaymentController.php
│   │   │   ├── StripeWebhookController.php
│   │   │   └── PayPalWebhookController.php
│   │   ├── Coupon/
│   │   │   ├── CouponController.php
│   │   │   └── CouponValidationController.php
│   │   └── SuperAdmin/
│   │       ├── TenantController.php
│   │       ├── SubscriptionController.php
│   │       ├── AnalyticsController.php
│   │       └── SupportTicketController.php
│   │
│   ├── Models/                      # Database models (Active Record pattern)
│   │   ├── BaseModel.php            # Base model with common methods
│   │   ├── User.php
│   │   ├── Restaurant.php
│   │   ├── Branch.php
│   │   ├── Category.php
│   │   ├── MenuItem.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Customer.php
│   │   ├── Payment.php
│   │   ├── Coupon.php
│   │   └── ...
│   │
│   ├── Services/                    # Business logic layer
│   │   ├── Auth/
│   │   │   ├── AuthService.php
│   │   │   ├── JWTService.php
│   │   │   └── PermissionService.php
│   │   ├── Menu/
│   │   │   ├── MenuService.php
│   │   │   └── MenuAvailabilityService.php
│   │   ├── Order/
│   │   │   ├── OrderService.php
│   │   │   ├── OrderCalculationService.php
│   │   │   └── OrderStatusService.php
│   │   ├── Payment/
│   │   │   ├── PaymentService.php
│   │   │   ├── StripeService.php
│   │   │   └── PayPalService.php
│   │   ├── Notification/
│   │   │   ├── NotificationService.php
│   │   │   ├── EmailService.php
│   │   │   └── SMSService.php
│   │   └── Coupon/
│   │       └── CouponService.php
│   │
│   ├── Middleware/                  # HTTP Middleware
│   │   ├── AuthMiddleware.php       # JWT authentication
│   │   ├── TenantMiddleware.php     # Multi-tenant isolation
│   │   ├── RateLimitMiddleware.php  # Rate limiting
│   │   ├── CorsMiddleware.php       # CORS handling
│   │   ├── ValidationMiddleware.php # Request validation
│   │   └── RoleMiddleware.php       # RBAC authorization
│   │
│   ├── Validators/                  # Request validators
│   │   ├── AuthValidator.php
│   │   ├── MenuItemValidator.php
│   │   ├── OrderValidator.php
│   │   └── ...
│   │
│   ├── Helpers/                     # Helper functions
│   │   ├── ResponseHelper.php       # Standardized API responses
│   │   ├── FileHelper.php           # File upload handling
│   │   ├── DateHelper.php           # Date/time utilities
│   │   └── StringHelper.php         # String utilities
│   │
│   ├── Jobs/                        # Background jobs
│   │   ├── SendOrderEmailJob.php
│   │   ├── SendOrderSMSJob.php
│   │   ├── ProcessWebhookJob.php
│   │   └── GenerateReportJob.php
│   │
│   ├── Core/                        # Core framework files
│   │   ├── Application.php          # Main application class
│   │   ├── Router.php               # Request router
│   │   ├── Request.php              # HTTP request handler
│   │   ├── Response.php             # HTTP response handler
│   │   ├── Database.php             # Database connection manager
│   │   ├── Cache.php                # Redis cache wrapper
│   │   ├── Queue.php                # Job queue manager
│   │   └── Container.php            # Dependency injection container
│   │
│   └── routes.php                   # Route definitions
│
├── storage/                         # Storage directory (not publicly accessible)
│   ├── logs/                        # Application logs
│   │   ├── app.log
│   │   ├── error.log
│   │   └── access.log
│   ├── cache/                       # File-based cache
│   ├── uploads/                     # Uploaded files (before processing)
│   └── temp/                        # Temporary files
│
├── tests/                           # Automated tests
│   ├── Unit/                        # Unit tests
│   │   ├── Services/
│   │   └── Models/
│   ├── Feature/                     # Feature tests (API tests)
│   │   ├── AuthTest.php
│   │   ├── MenuTest.php
│   │   └── OrderTest.php
│   └── Integration/                 # Integration tests
│
├── .env.example                     # Environment variables template
├── .env                             # Environment variables (gitignored)
├── .gitignore                       # Git ignore rules
├── composer.json                    # PHP dependencies
├── composer.lock                    # Locked dependencies
├── cli.php                          # CLI tool for migrations, seeds, etc.
├── docker-compose.yml               # Docker setup
├── Dockerfile                       # Docker image definition
├── nginx.conf                       # Nginx configuration
├── README.md                        # Project documentation
└── phpunit.xml                      # PHPUnit configuration
```

---

## **2. Module Organization**

### **2.1 Controller Layer**
- Handle HTTP requests
- Validate input (via Validators)
- Call Service layer for business logic
- Return standardized responses

```php
// Example structure
class MenuItemController extends BaseController {
    private $menuService;

    public function index() {
        // GET /api/v1/menu-items
    }

    public function show($id) {
        // GET /api/v1/menu-items/{id}
    }

    public function store() {
        // POST /api/v1/menu-items
    }

    public function update($id) {
        // PUT /api/v1/menu-items/{id}
    }

    public function destroy($id) {
        // DELETE /api/v1/menu-items/{id}
    }
}
```

### **2.2 Service Layer**
- Business logic implementation
- Database transactions
- External API calls
- Event dispatching

```php
// Example structure
class OrderService {
    public function createOrder($data);
    public function calculateTotal($items, $couponCode);
    public function updateStatus($orderId, $status);
    public function cancelOrder($orderId, $reason);
}
```

### **2.3 Model Layer**
- Database interaction
- Query building
- Relationships
- Scopes (e.g., tenant scope)

```php
// Example structure
class Order extends BaseModel {
    protected $table = 'orders';
    protected $fillable = ['customer_id', 'total', 'status'];

    public function items() {
        // hasMany relationship
    }

    public function customer() {
        // belongsTo relationship
    }
}
```

### **2.4 Middleware Pipeline**

Request flow through middleware:

```
Request
   │
   ▼
[CorsMiddleware] → Add CORS headers
   │
   ▼
[RateLimitMiddleware] → Check rate limits
   │
   ▼
[AuthMiddleware] → Verify JWT
   │
   ▼
[TenantMiddleware] → Set tenant scope
   │
   ▼
[RoleMiddleware] → Check permissions
   │
   ▼
[ValidationMiddleware] → Validate request
   │
   ▼
Controller
```

---

## **3. Configuration Files**

### **3.1 config/app.php**
```php
return [
    'name' => env('APP_NAME', 'Restaurant SaaS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
];
```

### **3.2 config/database.php**
```php
return [
    'driver' => env('DB_CONNECTION', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'restaurant_saas'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

### **3.3 config/payment.php**
```php
return [
    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
    ],
];
```

---

## **4. Entry Point (public/index.php)**

```php
<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create application instance
$app = new Application(__DIR__ . '/..');

// Load routes
require_once __DIR__ . '/../src/routes.php';

// Run application
$app->run();
```

---

## **5. Routing System**

### **5.1 Route Definition (src/routes.php)**

```php
<?php
// src/routes.php

use App\Core\Router;

$router = new Router();

// ============================================
// PUBLIC ROUTES (No auth required)
// ============================================

$router->post('/api/v1/auth/register', 'Auth\RegisterController@register');
$router->post('/api/v1/auth/login', 'Auth\AuthController@login');
$router->post('/api/v1/auth/forgot-password', 'Auth\PasswordResetController@sendResetLink');
$router->post('/api/v1/auth/reset-password', 'Auth\PasswordResetController@reset');

// Customer menu browsing (public)
$router->get('/api/v1/restaurants/:slug/menu', 'Menu\MenuItemController@getPublicMenu');
$router->get('/api/v1/menu-items/:id', 'Menu\MenuItemController@show');

// ============================================
// AUTHENTICATED ROUTES
// ============================================

$router->group(['middleware' => ['auth', 'tenant']], function($router) {

    // Auth
    $router->post('/api/v1/auth/logout', 'Auth\AuthController@logout');
    $router->post('/api/v1/auth/refresh', 'Auth\AuthController@refresh');
    $router->get('/api/v1/auth/me', 'Auth\AuthController@me');

    // Dashboard
    $router->get('/api/v1/dashboard/stats', 'Restaurant\DashboardController@stats');

    // Restaurant Management
    $router->get('/api/v1/restaurants', 'Restaurant\RestaurantController@index');
    $router->get('/api/v1/restaurants/:id', 'Restaurant\RestaurantController@show');
    $router->put('/api/v1/restaurants/:id', 'Restaurant\RestaurantController@update');

    // Branches
    $router->get('/api/v1/branches', 'Restaurant\BranchController@index');
    $router->post('/api/v1/branches', 'Restaurant\BranchController@store');
    $router->get('/api/v1/branches/:id', 'Restaurant\BranchController@show');
    $router->put('/api/v1/branches/:id', 'Restaurant\BranchController@update');
    $router->delete('/api/v1/branches/:id', 'Restaurant\BranchController@destroy');

    // Menu Categories
    $router->get('/api/v1/categories', 'Menu\CategoryController@index');
    $router->post('/api/v1/categories', 'Menu\CategoryController@store');
    $router->put('/api/v1/categories/:id', 'Menu\CategoryController@update');
    $router->delete('/api/v1/categories/:id', 'Menu\CategoryController@destroy');

    // Menu Items
    $router->get('/api/v1/menu-items', 'Menu\MenuItemController@index');
    $router->post('/api/v1/menu-items', 'Menu\MenuItemController@store');
    $router->put('/api/v1/menu-items/:id', 'Menu\MenuItemController@update');
    $router->delete('/api/v1/menu-items/:id', 'Menu\MenuItemController@destroy');

    // Orders
    $router->get('/api/v1/orders', 'Order\OrderController@index');
    $router->post('/api/v1/orders', 'Order\OrderController@store');
    $router->get('/api/v1/orders/:id', 'Order\OrderController@show');
    $router->put('/api/v1/orders/:id/status', 'Order\OrderStatusController@update');
    $router->delete('/api/v1/orders/:id', 'Order\OrderController@cancel');

    // Customers
    $router->get('/api/v1/customers', 'Customer\CustomerController@index');
    $router->get('/api/v1/customers/:id', 'Customer\CustomerController@show');

    // Coupons
    $router->get('/api/v1/coupons', 'Coupon\CouponController@index');
    $router->post('/api/v1/coupons', 'Coupon\CouponController@store');
    $router->post('/api/v1/coupons/validate', 'Coupon\CouponValidationController@validate');

    // Reports
    $router->get('/api/v1/reports/sales', 'Restaurant\ReportController@sales');
    $router->get('/api/v1/reports/orders', 'Restaurant\ReportController@orders');
});

// ============================================
// SUPER ADMIN ROUTES
// ============================================

$router->group(['middleware' => ['auth', 'role:super_admin']], function($router) {
    $router->get('/api/v1/admin/tenants', 'SuperAdmin\TenantController@index');
    $router->get('/api/v1/admin/tenants/:id', 'SuperAdmin\TenantController@show');
    $router->put('/api/v1/admin/tenants/:id', 'SuperAdmin\TenantController@update');
    $router->get('/api/v1/admin/analytics', 'SuperAdmin\AnalyticsController@index');
});

// ============================================
// WEBHOOKS
// ============================================

$router->post('/api/v1/webhooks/stripe', 'Payment\StripeWebhookController@handle');
$router->post('/api/v1/webhooks/paypal', 'Payment\PayPalWebhookController@handle');

return $router;
```

---

## **6. Environment Variables (.env)**

```env
# Application
APP_NAME="Restaurant SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourapp.com
APP_TIMEZONE=UTC
APP_LOCALE=en

# JWT
JWT_SECRET=your-secret-key-here-change-in-production
JWT_EXPIRATION=900
JWT_REFRESH_EXPIRATION=604800

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=restaurant_saas
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Mail
MAIL_DRIVER=sendgrid
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Restaurant SaaS"
SENDGRID_API_KEY=your-sendgrid-api-key

# SMS
SMS_DRIVER=twilio
TWILIO_ACCOUNT_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_FROM_NUMBER=+1234567890

# Stripe
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayPal
PAYPAL_CLIENT_ID=your-client-id
PAYPAL_SECRET=your-secret
PAYPAL_MODE=sandbox

# Google Maps
GOOGLE_MAPS_API_KEY=your-google-maps-key

# File Storage
STORAGE_DRIVER=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Logging
LOG_LEVEL=info
LOG_CHANNEL=daily
```

---

## **7. CLI Tool (cli.php)**

```php
<?php
// cli.php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$command = $argv[1] ?? null;

switch ($command) {
    case 'migrate:run':
        runMigrations();
        break;

    case 'migrate:rollback':
        rollbackMigrations();
        break;

    case 'db:seed':
        runSeeders();
        break;

    case 'queue:work':
        processQueue();
        break;

    case 'cache:clear':
        clearCache();
        break;

    default:
        echo "Unknown command\n";
        echo "Available commands:\n";
        echo "  migrate:run       - Run database migrations\n";
        echo "  migrate:rollback  - Rollback last migration\n";
        echo "  db:seed           - Seed database\n";
        echo "  queue:work        - Process background jobs\n";
        echo "  cache:clear       - Clear Redis cache\n";
}
```

---

## **8. Autoloading (composer.json)**

```json
{
    "name": "restaurant/online-order-saas",
    "description": "Restaurant Online Ordering SaaS Platform",
    "type": "project",
    "require": {
        "php": "^8.2",
        "ext-pdo": "*",
        "ext-redis": "*",
        "ext-json": "*",
        "vlucas/phpdotenv": "^5.5",
        "firebase/php-jwt": "^6.8",
        "stripe/stripe-php": "^10.0",
        "guzzlehttp/guzzle": "^7.7",
        "respect/validation": "^2.2",
        "phpmailer/phpmailer": "^6.8"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "squizlabs/php_codesniffer": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        },
        "files": [
            "src/Helpers/functions.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "cs": "phpcs"
    }
}
```

---

## **9. Code Style Standards**

### **9.1 PSR Standards**
- PSR-1: Basic Coding Standard
- PSR-4: Autoloading Standard
- PSR-12: Extended Coding Style

### **9.2 Naming Conventions**
- **Classes**: PascalCase (e.g., `OrderController`)
- **Methods**: camelCase (e.g., `createOrder`)
- **Variables**: camelCase (e.g., `$customerId`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `MAX_RETRIES`)
- **Database Tables**: snake_case (e.g., `menu_items`)

### **9.3 File Naming**
- **Controllers**: `*Controller.php`
- **Models**: `*.php` (singular)
- **Services**: `*Service.php`
- **Middleware**: `*Middleware.php`

---

## **10. Security Best Practices**

1. **Input Validation**: Validate all user inputs
2. **SQL Injection Prevention**: Use PDO prepared statements
3. **XSS Protection**: Escape output using `htmlspecialchars()`
4. **CSRF Protection**: Implement CSRF tokens for forms
5. **Password Hashing**: Use `password_hash()` with bcrypt
6. **JWT Security**: Use strong secret keys, short expiration
7. **Rate Limiting**: Implement API rate limiting
8. **HTTPS Only**: Force HTTPS in production
9. **Error Handling**: Don't expose sensitive data in errors
10. **Audit Logging**: Log all sensitive operations

---

**Document Version:** 1.0
**Last Updated:** 2025-11-19

