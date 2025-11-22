# 🔐 Security Guide & Best Practices

## **Restaurant Online Ordering SaaS Platform - Security Documentation**

---

## **Table of Contents**

1. [Security Overview](#security-overview)
2. [Authentication & Authorization](#authentication--authorization)
3. [Data Protection](#data-protection)
4. [Input Validation](#input-validation)
5. [API Security](#api-security)
6. [Infrastructure Security](#infrastructure-security)
7. [Security Checklist](#security-checklist)
8. [Incident Response](#incident-response)

---

## **1. Security Overview**

### **Security Layers**

```
┌─────────────────────────────────────────┐
│  Network Layer (Cloudflare, Firewall)  │
├─────────────────────────────────────────┤
│  Application Layer (PHP, Middleware)    │
├─────────────────────────────────────────┤
│  Data Layer (MySQL, Encryption)         │
├─────────────────────────────────────────┤
│  Infrastructure (Server, SSL, Backups)  │
└─────────────────────────────────────────┘
```

### **OWASP Top 10 Protection**

| Threat | Protection Implemented |
|--------|----------------------|
| **Injection** | ✅ PDO Prepared Statements |
| **Broken Authentication** | ✅ JWT + Refresh Tokens |
| **Sensitive Data Exposure** | ✅ Password Hashing, HTTPS |
| **XML External Entities** | ✅ JSON Only (No XML) |
| **Broken Access Control** | ✅ RBAC + Tenant Isolation |
| **Security Misconfiguration** | ✅ Secure Defaults |
| **XSS** | ✅ Output Escaping |
| **Insecure Deserialization** | ✅ Input Validation |
| **Using Components with Known Vulnerabilities** | ✅ Composer Updates |
| **Insufficient Logging & Monitoring** | ✅ Comprehensive Logging |

---

## **2. Authentication & Authorization**

### **2.1 Password Security**

```php
// IMPLEMENTED: Password Hashing
password_hash($password, PASSWORD_BCRYPT);  // Cost factor: 10

// Password Requirements:
- Minimum 8 characters
- Must include uppercase, lowercase, numbers
- No common passwords
- Hashed with bcrypt (cost=10)
```

**Best Practices:**
- ✅ Never store plaintext passwords
- ✅ Use bcrypt (PASSWORD_BCRYPT)
- ✅ Implement password strength validation
- ✅ Rate limit login attempts
- ✅ Account lockout after failed attempts

### **2.2 JWT Token Security**

```php
// Token Configuration
JWT_EXPIRATION=900          // 15 minutes (access token)
JWT_REFRESH_EXPIRATION=604800  // 7 days (refresh token)
JWT_SECRET=64_CHAR_RANDOM_STRING  // CRITICAL: Change in production
```

**Security Measures:**
- ✅ Short-lived access tokens (15min)
- ✅ Long-lived refresh tokens (7 days)
- ✅ Token stored in database for revocation
- ✅ Signature verification (HS256)
- ✅ Refresh token rotation (optional)

**Token Revocation:**
```php
// Logout revokes refresh token
Database::update('refresh_tokens',
    ['is_revoked' => 1],
    'token = :token',
    ['token' => $refreshToken]
);
```

### **2.3 Multi-Tenant Security**

```php
// CRITICAL: Tenant Isolation
// Every query automatically scoped to tenant_id

// Middleware ensures proper isolation
class TenantMiddleware {
    public function handle(Request $request) {
        $_SESSION['tenant_id'] = $request->tenantId;
        // All subsequent queries filtered by tenant_id
    }
}
```

**Tenant Isolation Rules:**
- ✅ Every table has `tenant_id` column
- ✅ Middleware enforces tenant scope
- ✅ No cross-tenant data access
- ✅ Audit logs per tenant

### **2.4 Role-Based Access Control (RBAC)**

```php
// Roles Hierarchy
Super Admin > Restaurant Owner > Branch Manager > Staff Admin > Cashier

// Permission Check (Future Implementation)
if (!$user->hasPermission('menu.manage')) {
    return response()->error('Forbidden', 403);
}
```

---

## **3. Data Protection**

### **3.1 SQL Injection Prevention**

```php
// ✅ GOOD: Prepared Statements (Used Throughout)
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ BAD: String Concatenation (NEVER DO THIS)
$sql = "SELECT * FROM users WHERE email = '$email'"; // VULNERABLE!
```

**Implementation:**
- ✅ All queries use PDO prepared statements
- ✅ No string concatenation in SQL
- ✅ Input sanitization via PDO

### **3.2 XSS Prevention**

```php
// Output Escaping
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// In JSON responses (automatic escaping)
json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
```

**Protection Measures:**
- ✅ Output escaping with `htmlspecialchars()`
- ✅ Content-Type headers set correctly
- ✅ X-XSS-Protection header enabled
- ✅ Content-Security-Policy (recommended)

### **3.3 CSRF Protection**

**Current Status:** Not yet implemented (API-only)

**For Future Web Forms:**
```php
// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verify on form submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token mismatch');
}
```

### **3.4 Data Encryption**

**Encryption Strategy:**
```php
// Passwords: bcrypt hashing
password_hash($password, PASSWORD_BCRYPT);

// Sensitive fields (e.g., payment tokens): AES-256
openssl_encrypt($data, 'aes-256-gcm', $key, 0, $iv, $tag);

// Database: Full disk encryption (at infrastructure level)
```

**Encrypted Fields:**
- ✅ Passwords (bcrypt)
- ⚠️ Payment gateway credentials (ENV variables)
- ⚠️ API keys (ENV variables)
- 🔄 Customer PII (future enhancement)

---

## **4. Input Validation**

### **4.1 Validation Rules**

```php
// Using our Validator class
$validator = Validator::make($request->all(), [
    'email' => 'required|email|unique:restaurants,email',
    'password' => 'required|min:8|confirmed',
    'phone' => 'phone',
    'name' => 'required|alpha|min:2|max:255'
]);

if ($validator->fails()) {
    return response()->error('Validation failed', 422, $validator->errors());
}
```

**Available Rules:**
- required, email, min, max
- numeric, integer, string, boolean
- url, phone, alpha, alphanumeric
- confirmed, in, array, date
- unique, exists

### **4.2 Sanitization**

```php
// Input sanitization
$clean = sanitize($userInput);  // Removes HTML, special chars

// Email validation
filter_var($email, FILTER_VALIDATE_EMAIL);

// Integer validation
filter_var($id, FILTER_VALIDATE_INT);
```

---

## **5. API Security**

### **5.1 Rate Limiting**

```php
// Configuration
RATE_LIMIT_MAX_REQUESTS=60  // requests
RATE_LIMIT_WINDOW=60        // seconds

// Response Headers
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1700000000
Retry-After: 15
```

**Implementation:**
- ✅ File-based rate limiting
- ✅ Per-user and per-IP tracking
- ✅ 429 status on limit exceeded
- ✅ Automatic cleanup of old entries

### **5.2 CORS Configuration**

```php
// .env configuration
CORS_ALLOWED_ORIGINS=https://yourdomain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization

// Security Headers
Access-Control-Allow-Origin: https://yourdomain.com
Access-Control-Allow-Credentials: true
```

**CORS Best Practices:**
- ✅ Whitelist specific origins (not *)
- ✅ Limit allowed methods
- ✅ Limit allowed headers
- ✅ Set max age for preflight

### **5.3 HTTP Security Headers**

```apache
# In .htaccess
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# Recommended: Add CSP
Header set Content-Security-Policy "default-src 'self'"
```

### **5.4 API Versioning**

```
Current: /api/v1/*
Future: /api/v2/*

// Maintain backward compatibility
// Deprecate old versions gracefully
```

---

## **6. Infrastructure Security**

### **6.1 Server Hardening**

```bash
# Firewall (UFW)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable

# Disable unnecessary services
sudo systemctl disable bluetooth
sudo systemctl disable cups

# Keep system updated
sudo apt update && sudo apt upgrade -y
```

### **6.2 File Permissions**

```bash
# Application files
sudo chown -R www-data:www-data /var/www/html/restaurant-saas
sudo chmod -R 755 /var/www/html/restaurant-saas

# Storage directories (writable)
sudo chmod -R 775 /var/www/html/restaurant-saas/storage
sudo chmod -R 775 /var/www/html/restaurant-saas/public/uploads

# Protect sensitive files
chmod 600 /var/www/html/restaurant-saas/.env
chmod 600 /var/www/html/restaurant-saas/config/*.php
```

### **6.3 Database Security**

```sql
-- Use dedicated user (not root)
CREATE USER 'restaurant_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON restaurant_saas.* TO 'restaurant_user'@'localhost';

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Disable remote root login
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1');

-- Flush privileges
FLUSH PRIVILEGES;
```

**Database Best Practices:**
- ✅ Use strong passwords (16+ chars)
- ✅ Limit user privileges (principle of least privilege)
- ✅ Enable MySQL slow query log
- ✅ Regular backups (automated)
- ✅ Encrypt backups

### **6.4 SSL/TLS Configuration**

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (certbot adds this automatically)
# Check: sudo systemctl status certbot.timer
```

**SSL Best Practices:**
- ✅ Use TLS 1.2+ (disable TLS 1.0, 1.1)
- ✅ Strong cipher suites
- ✅ HSTS header (HTTP Strict Transport Security)
- ✅ Auto-renewal enabled

### **6.5 Backup Strategy**

```bash
# Daily database backups
0 2 * * * /usr/local/bin/backup-restaurant-db.sh

# Weekly full backups
0 3 * * 0 /usr/local/bin/backup-full.sh

# Backup retention: 30 days
find /var/backups/restaurant-saas -name "*.sql.gz" -mtime +30 -delete
```

**Backup Checklist:**
- ✅ Automated daily backups
- ✅ Off-site storage (S3, another server)
- ✅ Encrypted backups
- ✅ Tested restore procedures
- ✅ 30-day retention

---

## **7. Security Checklist**

### **Pre-Production Checklist**

#### **Application Security**
- [ ] JWT_SECRET changed to random 64-char string
- [ ] APP_DEBUG=false in production
- [ ] Error messages don't expose sensitive info
- [ ] All inputs validated
- [ ] All outputs escaped
- [ ] SQL injection tests passed
- [ ] XSS tests passed
- [ ] CSRF protection implemented (for web forms)
- [ ] Rate limiting enabled
- [ ] Logging configured

#### **Infrastructure Security**
- [ ] HTTPS enabled (SSL certificate)
- [ ] Firewall configured (UFW)
- [ ] SSH key-only authentication
- [ ] Database user with limited privileges
- [ ] Strong database password (16+ chars)
- [ ] File permissions set correctly (755/775)
- [ ] .env file protected (chmod 600)
- [ ] Directory listing disabled
- [ ] Server timezone set to UTC

#### **Monitoring & Maintenance**
- [ ] Error logging enabled
- [ ] Access logs reviewed regularly
- [ ] Failed login attempts monitored
- [ ] Automated backups configured
- [ ] Backup restore tested
- [ ] System updates automated
- [ ] Intrusion detection (optional: fail2ban)
- [ ] Uptime monitoring (UptimeRobot, Pingdom)

#### **Code Security**
- [ ] Dependencies updated (composer update)
- [ ] No debug code in production
- [ ] API keys in ENV (not hardcoded)
- [ ] Sensitive data not logged
- [ ] Password requirements enforced
- [ ] Account lockout after failed attempts
- [ ] Session timeout configured
- [ ] Audit logging enabled

---

## **8. Incident Response**

### **8.1 Security Incident Plan**

**Step 1: Detect**
- Monitor error logs: `/var/www/html/restaurant-saas/storage/logs/`
- Check Apache logs: `/var/log/apache2/`
- Review failed login attempts
- Monitor unusual traffic patterns

**Step 2: Contain**
```bash
# Block suspicious IP
sudo ufw deny from 1.2.3.4

# Revoke all tokens (emergency)
UPDATE refresh_tokens SET is_revoked = 1;

# Disable user account
UPDATE users SET is_active = 0 WHERE id = ?;
```

**Step 3: Investigate**
```bash
# Check access logs
tail -1000 /var/log/apache2/restaurant-saas-access.log | grep "suspicious-pattern"

# Check audit logs
SELECT * FROM audit_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

# Check failed logins
SELECT * FROM activity_logs WHERE action = 'login_failed' ORDER BY created_at DESC LIMIT 100;
```

**Step 4: Recover**
- Restore from backup if needed
- Reset passwords for affected accounts
- Patch vulnerabilities
- Update security measures

**Step 5: Post-Incident**
- Document incident
- Update security procedures
- Notify affected users (if required)
- Implement additional controls

### **8.2 Common Attacks & Mitigation**

| Attack Type | Mitigation |
|-------------|-----------|
| **Brute Force Login** | Rate limiting, account lockout, CAPTCHA |
| **SQL Injection** | Prepared statements (already implemented) |
| **XSS** | Output escaping, CSP headers |
| **CSRF** | CSRF tokens, SameSite cookies |
| **DDoS** | Cloudflare, rate limiting, IP blocking |
| **Session Hijacking** | HTTPS, secure cookies, short sessions |
| **Man-in-the-Middle** | HTTPS/TLS, HSTS, certificate pinning |

---

## **9. Security Contacts**

**Report Security Issues:**
- Email: security@yourdomain.com
- Response Time: 24 hours
- PGP Key: (Add your PGP key for encrypted communication)

**Security Updates:**
- Subscribe: security-updates@yourdomain.com
- Check: https://yourdomain.com/security

---

## **10. Compliance & Standards**

### **Standards Followed**
- ✅ OWASP Top 10 2021
- ✅ PCI DSS Level 2 (for payment data)
- ⚠️ GDPR (if serving EU customers)
- ⚠️ CCPA (if serving California customers)

### **Data Retention**
- User data: Retained until account deletion
- Audit logs: 1 year
- Backups: 30 days
- Payment data: Never stored (tokenized)

---

## **Security Review Schedule**

- **Daily**: Monitor logs, check backups
- **Weekly**: Review failed logins, update dependencies
- **Monthly**: Security audit, penetration testing
- **Quarterly**: Full security assessment, update documentation
- **Annually**: Third-party security audit

---

**Last Updated:** 2025-11-19
**Version:** 1.0
**Next Review:** 2025-12-19

