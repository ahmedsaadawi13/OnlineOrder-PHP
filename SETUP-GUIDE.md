# 🚀 Setup Guide for /var/www/html

Complete guide to set up the Restaurant SaaS Platform on a server with Apache/PHP.

---

## **Prerequisites**

- Linux server (Ubuntu 20.04+ recommended)
- Apache 2.4+
- PHP 8.2+ with extensions:
  - php-mbstring
  - php-xml
  - php-mysql
  - php-curl
  - php-redis (optional but recommended)
- MySQL 8.0+ or MariaDB 10.5+
- Composer
- Git

---

## **Step 1: Install Required Software**

### **Update system**
```bash
sudo apt update && sudo apt upgrade -y
```

### **Install Apache**
```bash
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

### **Install PHP 8.2**
```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-redis -y
```

### **Install MySQL**
```bash
sudo apt install mysql-server -y
sudo mysql_secure_installation
```

### **Install Composer**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

---

## **Step 2: Clone & Setup Application**

### **Navigate to web root**
```bash
cd /var/www/html
```

### **Clone repository** (or upload files)
```bash
# If using Git
sudo git clone https://github.com/yourusername/OnlineOrder-PHP.git restaurant-saas
cd restaurant-saas

# OR if uploading, extract your files here
```

### **Set permissions**
```bash
sudo chown -R www-data:www-data /var/www/html/restaurant-saas
sudo chmod -R 755 /var/www/html/restaurant-saas
sudo chmod -R 775 /var/www/html/restaurant-saas/storage
sudo chmod -R 775 /var/www/html/restaurant-saas/public/uploads
```

---

## **Step 3: Install Dependencies**

```bash
cd /var/www/html/restaurant-saas
composer install --no-dev --optimize-autoloader
```

---

## **Step 4: Configure Environment**

### **Create .env file**
```bash
cp .env.example .env
nano .env
```

### **Edit .env with your settings**
```env
# Application
APP_NAME="Restaurant SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# JWT
JWT_SECRET=CHANGE_THIS_TO_RANDOM_STRING_64_CHARS
JWT_EXPIRATION=900
JWT_REFRESH_EXPIRATION=604800

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=restaurant_saas
DB_USERNAME=restaurant_user
DB_PASSWORD=YOUR_SECURE_PASSWORD_HERE

# Redis (optional)
REDIS_HOST=localhost
REDIS_PORT=6379
```

**IMPORTANT**: Generate a strong JWT secret:
```bash
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## **Step 5: Create Database**

### **Login to MySQL**
```bash
sudo mysql -u root -p
```

### **Create database and user**
```sql
CREATE DATABASE restaurant_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'restaurant_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD_HERE';

GRANT ALL PRIVILEGES ON restaurant_saas.* TO 'restaurant_user'@'localhost';

FLUSH PRIVILEGES;

EXIT;
```

---

## **Step 6: Run Migrations**

```bash
cd /var/www/html/restaurant-saas
php cli.php migrate:run
```

You should see:
```
Running database migrations...

Running migration: 001_create_restaurants_table.sql
✓ Completed: 001_create_restaurants_table.sql
Running migration: 002_create_roles_and_permissions_tables.sql
✓ Completed: 002_create_roles_and_permissions_tables.sql
...

All migrations completed successfully!
```

---

## **Step 7: Configure Apache**

### **Create Apache virtual host**
```bash
sudo nano /etc/apache2/sites-available/restaurant-saas.conf
```

### **Add this configuration**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    ServerAdmin admin@yourdomain.com

    DocumentRoot /var/www/html/restaurant-saas/public

    <Directory /var/www/html/restaurant-saas/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/restaurant-saas-error.log
    CustomLog ${APACHE_LOG_DIR}/restaurant-saas-access.log combined

    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

### **Enable site and modules**
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2ensite restaurant-saas.conf
sudo systemctl restart apache2
```

---

## **Step 8: Setup SSL (Recommended)**

### **Install Certbot**
```bash
sudo apt install certbot python3-certbot-apache -y
```

### **Get SSL certificate**
```bash
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

Follow the prompts. Certbot will automatically configure SSL and set up auto-renewal.

---

## **Step 9: Test Installation**

### **Test database connection**
```bash
php cli.php db:test
```

### **Test API endpoint**
```bash
curl http://yourdomain.com/api/v1/health
```

You should see:
```json
{
  "success": true,
  "data": {
    "status": "ok"
  }
}
```

---

## **Step 10: Create First Restaurant**

### **Using API (Postman/curl)**
```bash
curl -X POST http://yourdomain.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_name": "My Restaurant",
    "email": "owner@myrestaurant.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890"
  }'
```

---

## **Security Checklist**

- [ ] Change JWT_SECRET in .env
- [ ] Set APP_DEBUG=false in production
- [ ] Use strong database password
- [ ] Enable SSL (HTTPS)
- [ ] Set proper file permissions (755 for files, 775 for storage)
- [ ] Configure firewall (UFW)
  ```bash
  sudo ufw allow 22/tcp
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw enable
  ```
- [ ] Keep PHP and MySQL updated
- [ ] Set up regular database backups
- [ ] Disable directory listing (already in .htaccess)
- [ ] Hide .env file from web access

---

## **Performance Optimization (Optional)**

### **Enable OPcache**
```bash
sudo nano /etc/php/8.2/apache2/php.ini
```

Add/uncomment:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### **Install Redis (for caching)**
```bash
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## **Backup Strategy**

### **Database backup script**
```bash
sudo nano /usr/local/bin/backup-restaurant-db.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/restaurant-saas"
mkdir -p $BACKUP_DIR

mysqldump -u restaurant_user -p'YOUR_PASSWORD' restaurant_saas \
  | gzip > $BACKUP_DIR/restaurant_saas_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

```bash
sudo chmod +x /usr/local/bin/backup-restaurant-db.sh
```

### **Schedule daily backups**
```bash
sudo crontab -e
```

Add:
```
0 2 * * * /usr/local/bin/backup-restaurant-db.sh
```

---

## **Monitoring & Logs**

### **Application logs**
```bash
tail -f /var/www/html/restaurant-saas/storage/logs/error.log
tail -f /var/www/html/restaurant-saas/storage/logs/app.log
```

### **Apache logs**
```bash
tail -f /var/log/apache2/restaurant-saas-error.log
tail -f /var/log/apache2/restaurant-saas-access.log
```

### **MySQL logs**
```bash
sudo tail -f /var/log/mysql/error.log
```

---

## **Troubleshooting**

### **Issue: 500 Internal Server Error**
- Check Apache error log: `sudo tail -f /var/log/apache2/restaurant-saas-error.log`
- Verify .htaccess is working: `sudo a2enmod rewrite && sudo systemctl restart apache2`
- Check file permissions: `sudo chown -R www-data:www-data /var/www/html/restaurant-saas`

### **Issue: Database connection failed**
- Test connection: `php cli.php db:test`
- Verify MySQL is running: `sudo systemctl status mysql`
- Check .env database credentials

### **Issue: Composer dependencies missing**
```bash
cd /var/www/html/restaurant-saas
composer install
```

### **Issue: JWT errors**
- Ensure JWT_SECRET is set in .env
- Check that firebase/php-jwt is installed: `composer show | grep jwt`

---

## **Update/Deployment Procedure**

When updating the application:

```bash
cd /var/www/html/restaurant-saas

# Backup database first!
/usr/local/bin/backup-restaurant-db.sh

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations (if any new)
php cli.php migrate:run

# Clear cache
php cli.php cache:clear

# Restart Apache
sudo systemctl restart apache2
```

---

## **Production URLs**

After setup, your API will be available at:
- **API Base URL**: `https://yourdomain.com/api/v1`
- **Health Check**: `https://yourdomain.com/api/v1/health`
- **Register**: `https://yourdomain.com/api/v1/auth/register`
- **Login**: `https://yourdomain.com/api/v1/auth/login`

---

## **Need Help?**

- Check logs in `/var/www/html/restaurant-saas/storage/logs/`
- Review Apache logs: `/var/log/apache2/`
- Test database: `php cli.php db:test`
- Verify permissions: `ls -la /var/www/html/restaurant-saas`

---

**Setup Complete! 🎉**

Your Restaurant SaaS Platform is now running in production at `/var/www/html`.
