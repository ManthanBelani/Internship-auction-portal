# BidOrbit - Production Deployment Guide

## Complete Guide for Deploying BidOrbit Auction Portal

This guide covers the complete deployment process for making BidOrbit production-ready.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Requirements](#server-requirements)
3. [Database Setup](#database-setup)
4. [Backend Deployment](#backend-deployment)
5. [WebSocket Server](#websocket-server)
6. [Flutter App Build](#flutter-app-build)
7. [SSL/HTTPS Configuration](#sslhttps-configuration)
8. [Performance Optimization](#performance-optimization)
9. [Security Checklist](#security-checklist)
10. [Monitoring & Logging](#monitoring--logging)
11. [Backup Strategy](#backup-strategy)

---

## Prerequisites

### Required Software
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.5+
- Composer
- Node.js 18+ (for build tools)
- Flutter SDK 3.10+
- SSL Certificate (Let's Encrypt recommended)
- Redis (optional, for caching)

### Required Accounts
- Domain name
- SSL Certificate
- Stripe Account (for payments)
- SMTP Service (SendGrid, Mailgun, or Amazon SES)

---

## Server Requirements

### Minimum Hardware
- CPU: 2 vCPUs
- RAM: 4 GB
- Storage: 50 GB SSD
- Bandwidth: 1 TB/month

### Recommended Hardware (Production)
- CPU: 4 vCPUs
- RAM: 8 GB
- Storage: 100 GB SSD
- Bandwidth: Unlimited

### Software Stack
```bash
# Ubuntu 22.04 LTS

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-mbstring \
    php8.1-xml php8.1-zip php8.1-gd php8.1-bcmath php8.1-intl

# Install MySQL
sudo apt install -y mysql-server mysql-client

# Install Nginx
sudo apt install -y nginx

# Install Redis (optional)
sudo apt install -y redis-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Database Setup

### 1. Create MySQL Database

```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE bidorbit_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bidorbit'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON bidorbit_prod.* TO 'bidorbit'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Import Schema

```bash
# Import the MySQL schema
mysql -u bidorbit -p bidorbit_prod < database/migrations/mysql_schema.sql
```

### 3. Configure Database Connection

Update `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bidorbit_prod
DB_USERNAME=bidorbit
DB_PASSWORD=your_secure_password
```

---

## Backend Deployment

### 1. Clone and Setup

```bash
# Clone repository
cd /var/www
git clone https://github.com/your-repo/bidorbit.git
cd bidorbit

# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.production .env

# Generate application key
php artisan key:generate  # If using Laravel
# Or manually set JWT_SECRET in .env
```

### 2. Configure Environment

Edit `.env`:
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.bidorbit.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bidorbit_prod
DB_USERNAME=bidorbit
DB_PASSWORD=your_secure_password

# JWT
JWT_SECRET=your_64_character_random_string_here
JWT_EXPIRES_IN=3600

# Stripe
STRIPE_SECRET_KEY=sk_live_your_key
STRIPE_PUBLISHABLE_KEY=pk_live_your_key

# Email
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@bidorbit.com
MAIL_FROM_NAME="BidOrbit"

# Commission
COMMISSION_RATE=0.05
MINIMUM_COMMISSION=1.00
```

### 3. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/bidorbit

# Set permissions
sudo find /var/www/bidorbit -type f -exec chmod 644 {} \;
sudo find /var/www/bidorbit -type d -exec chmod 755 {} \;

# Writable directories
sudo chmod -R 775 /var/www/bidorbit/storage
sudo chmod -R 775 /var/www/bidorbit/logs
sudo chmod -R 775 /var/www/bidorbit/public/uploads
```

### 4. Nginx Configuration

Create `/etc/nginx/sites-available/bidorbit`:
```nginx
server {
    listen 80;
    server_name api.bidorbit.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.bidorbit.com;
    root /var/www/bidorbit/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.bidorbit.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.bidorbit.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req zone=api burst=20 nodelay;

    # Logging
    access_log /var/log/nginx/bidorbit-access.log;
    error_log /var/log/nginx/bidorbit-error.log;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Buffer settings
        fastcgi_buffer_size 16k;
        fastcgi_buffers 16 16k;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to sensitive files
    location ~* \.(env|log|json|lock|md)$ {
        deny all;
    }

    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:
```bash
sudo ln -s /etc/nginx/sites-available/bidorbit /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## WebSocket Server

### 1. Create Systemd Service

Create `/etc/systemd/system/bidorbit-websocket.service`:
```ini
[Unit]
Description=BidOrbit WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/bidorbit
ExecStart=/usr/bin/php websocket_server.php
Restart=always
RestartSec=5
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=bidorbit-websocket

[Install]
WantedBy=multi-user.target
```

### 2. Start Service

```bash
sudo systemctl daemon-reload
sudo systemctl enable bidorbit-websocket
sudo systemctl start bidorbit-websocket
sudo systemctl status bidorbit-websocket
```

### 3. WebSocket Proxy (Nginx)

Add to Nginx config:
```nginx
# WebSocket endpoint
upstream websocket {
    server 127.0.0.1:8081;
}

server {
    # ... existing config ...

    location /ws {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }
}
```

---

## Flutter App Build

### 1. Configure Production Environment

Update `lib/config/env_config.dart` or use build flags:
```bash
# Build with production settings
flutter build apk \
    --dart-define=ENVIRONMENT=production \
    --dart-define=API_BASE_URL=https://api.bidorbit.com/api \
    --dart-define=WS_BASE_URL=wss://ws.bidorbit.com \
    --release \
    --obfuscate \
    --split-debug-info=build/app/outputs/symbols
```

### 2. Android Release Build

```bash
cd BidOrbit/bidorbit

# Build release APK
flutter build apk --release

# Build App Bundle (for Play Store)
flutter build appbundle --release

# Build split APKs per ABI (smaller size)
flutter build apk --split-per-abi --release
```

### 3. iOS Release Build

```bash
# Build iOS release
flutter build ios --release

# Or build for specific device
flutter build ipa --release
```

### 4. Code Signing

#### Android
1. Create keystore:
```bash
keytool -genkey -v -keystore bidorbit-release.jks \
    -keyalg RSA -keysize 2048 -validity 10000 \
    -alias bidorbit
```

2. Configure `android/key.properties`:
```properties
storePassword=your_keystore_password
keyPassword=your_key_password
keyAlias=bidorbit
storeFile=../bidorbit-release.jks
```

3. Update `android/app/build.gradle.kts`:
```kotlin
android {
    // ...
    signingConfigs {
        create("release") {
            storeFile = file("../bidorbit-release.jks")
            storePassword = "your_keystore_password"
            keyAlias = "bidorbit"
            keyPassword = "your_key_password"
        }
    }
    buildTypes {
        release {
            signingConfig = signingConfigs.getByName("release")
        }
    }
}
```

---

## SSL/HTTPS Configuration

### Using Let's Encrypt

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d api.bidorbit.com -d ws.bidorbit.com

# Auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Manual Certificate Installation

```bash
# Copy certificates
sudo mkdir -p /etc/nginx/ssl
sudo cp your_certificate.crt /etc/nginx/ssl/
sudo cp your_private.key /etc/nginx/ssl/

# Set permissions
sudo chmod 600 /etc/nginx/ssl/your_private.key
```

---

## Performance Optimization

### 1. OPcache Configuration

Edit `/etc/php/8.1/fpm/conf.d/10-opcache.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.max_wasted_percentage=10
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

### 2. PHP-FPM Pool Configuration

Edit `/etc/php/8.1/fpm/pool.d/www.conf`:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### 3. MySQL Optimization

Edit `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
query_cache_size = 0
query_cache_type = 0
```

### 4. Redis Caching (Optional)

```bash
# Install Redis PHP extension
sudo apt install php8.1-redis

# Configure in .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Security Checklist

### Server Security
- [ ] Firewall configured (UFW)
- [ ] SSH key authentication only
- [ ] Root login disabled
- [ ] Fail2ban installed
- [ ] Automatic security updates enabled

### Application Security
- [ ] APP_DEBUG=false in production
- [ ] Strong JWT_SECRET (64+ characters)
- [ ] HTTPS enforced
- [ ] CORS configured properly
- [ ] Rate limiting enabled
- [ ] Input validation on all endpoints
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS protection headers
- [ ] CSRF protection enabled

### Database Security
- [ ] Strong root password
- [ ] Application-specific database user
- [ ] Remote access disabled
- [ ] Regular backups configured

### File Security
- [ ] Correct file permissions
- [ ] Sensitive files not accessible from web
- [ ] Upload directory not executable

---

## Monitoring & Logging

### 1. Application Logging

Logs are stored in `/var/www/bidorbit/logs/`:
- `app.log` - Application logs
- `error.log` - Error logs
- `access.log` - Access logs

### 2. Log Rotation

Create `/etc/logrotate.d/bidorbit`:
```
/var/www/bidorbit/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 3. Monitoring Tools

```bash
# Install monitoring tools
sudo apt install -y htop iotop nethogs

# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Check Nginx status
sudo systemctl status nginx

# Check MySQL status
sudo systemctl status mysql

# Check WebSocket status
sudo systemctl status bidorbit-websocket
```

### 4. Uptime Monitoring

Consider using:
- UptimeRobot (free)
- Pingdom
- New Relic
- Datadog

---

## Backup Strategy

### 1. Database Backup Script

Create `/usr/local/bin/backup-bidorbit.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/bidorbit"
DATE=$(date +%Y%m%d_%H%M%S)
MYSQL_USER="bidorbit"
MYSQL_PASS="your_password"
DATABASE="bidorbit_prod"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$MYSQL_USER -p$MYSQL_PASS $DATABASE | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz -C /var/www/bidorbit/public/uploads .

# Remove old backups (keep 30 days)
find $BACKUP_DIR -type f -mtime +30 -delete

# Upload to S3 (optional)
# aws s3 sync $BACKUP_DIR s3://your-bucket/backups/
```

### 2. Cron Job

```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/backup-bidorbit.sh >> /var/log/bidorbit-backup.log 2>&1
```

---

## Quick Deployment Commands

```bash
# Full deployment script
#!/bin/bash

echo "Deploying BidOrbit..."

# Pull latest code
cd /var/www/bidorbit
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan cache:clear  # If using Laravel
# Or manually clear cache directory

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart bidorbit-websocket
sudo systemctl reload nginx

echo "Deployment complete!"
```

---

## Support & Troubleshooting

### Common Issues

1. **502 Bad Gateway**
   - Check PHP-FPM is running: `sudo systemctl status php8.1-fpm`
   - Check Nginx error logs: `tail -f /var/log/nginx/error.log`

2. **WebSocket Connection Failed**
   - Check WebSocket service: `sudo systemctl status bidorbit-websocket`
   - Check port 8081 is open: `sudo netstat -tlnp | grep 8081`

3. **Database Connection Error**
   - Check MySQL is running: `sudo systemctl status mysql`
   - Verify credentials in `.env`

4. **Permission Denied**
   - Run: `sudo chown -R www-data:www-data /var/www/bidorbit`
   - Run: `sudo chmod -R 775 storage logs public/uploads`

---

**Document Version:** 1.0.0  
**Last Updated:** March 2026
