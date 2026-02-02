# Auction Portal - Quick Reference Guide

**Quick access to common tasks and information**

---

## 🚀 Quick Start

### Installation (5 minutes)
```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 3. Create database
mysql -u root -p -e "CREATE DATABASE auction_portal"

# 4. Run migrations
mysql -u root -p auction_portal < database/migrations/*.sql

# 5. Create admin user
mysql -u root -p auction_portal < database/create_admin_user.sql

# 6. Start server
composer start
```

### Access Points
- **API:** `http://localhost:8000/api`
- **Admin Dashboard:** `http://localhost/admin/login.php`
- **WebSocket:** `ws://localhost:8080`

### Default Credentials
- **Email:** admin@auction.com
- **Password:** admin123
- ⚠️ **Change immediately after first login!**

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| README.md | Main project overview |
| SYSTEM_OVERVIEW.md | Complete system documentation |
| API_ENDPOINTS.md | API reference |
| SETUP_GUIDE.md | Detailed installation |
| ADMIN_DASHBOARD_SETUP.md | Dashboard setup |
| FLUTTER_INTEGRATION_GUIDE.md | Flutter app integration |
| RBAC_IMPLEMENTATION_SUMMARY.md | Role-based access |
| PROJECT_STATUS.md | Current status |

---

## 🔑 User Roles

| Role | Create Items | Place Bids | Manage Users | View Earnings | Delete Content |
|------|--------------|------------|--------------|---------------|----------------|
| **Admin** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Moderator** | ❌ | ✅ | ⚠️ Limited | ❌ | ✅ |
| **Seller** | ✅ | ✅ | ❌ | ⚠️ Own only | ⚠️ Own only |
| **Buyer** | ❌ | ✅ | ❌ | ❌ | ❌ |

---

## 🌐 API Endpoints

### Authentication
```bash
# Register
POST /api/users/register
Body: { "name": "John", "email": "john@example.com", "password": "pass123", "role": "buyer" }

# Login
POST /api/users/login
Body: { "email": "john@example.com", "password": "pass123" }
Response: { "token": "jwt-token", "user": {...} }

# Get Profile (Protected)
GET /api/users/profile
Header: Authorization: Bearer {token}
```

### Items
```bash
# List all items
GET /api/items

# Get item details
GET /api/items/123

# Create item (Protected)
POST /api/items
Header: Authorization: Bearer {token}
Body: { "title": "Item", "description": "...", "starting_price": 100, "end_time": "2024-12-31 23:59:59" }

# Upload images (Protected)
POST /api/items/123/images
Header: Authorization: Bearer {token}
Body: FormData with image files
```

### Bidding
```bash
# Place bid (Protected)
POST /api/bids
Header: Authorization: Bearer {token}
Body: { "item_id": 123, "amount": 150 }

# Get bid history
GET /api/bids/123
```

### Watchlist
```bash
# Add to watchlist (Protected)
POST /api/watchlist
Header: Authorization: Bearer {token}
Body: { "item_id": 123 }

# Get watchlist (Protected)
GET /api/watchlist
Header: Authorization: Bearer {token}

# Remove from watchlist (Protected)
DELETE /api/watchlist/123
Header: Authorization: Bearer {token}
```

### Reviews
```bash
# Create review (Protected)
POST /api/reviews
Header: Authorization: Bearer {token}
Body: { "reviewed_user_id": 456, "rating": 5, "comment": "Great seller!" }

# Get user reviews
GET /api/users/456/reviews

# Get user rating
GET /api/users/456/rating
```

### Admin
```bash
# Get statistics (Admin only)
GET /api/admin/stats
Header: Authorization: Bearer {admin-token}

# List all users (Admin only)
GET /api/admin/users?role=seller&status=active
Header: Authorization: Bearer {admin-token}

# Suspend user (Admin/Moderator)
POST /api/admin/users/456/suspend
Header: Authorization: Bearer {admin-token}
Body: { "until": "2024-12-31 23:59:59" }

# Ban user (Admin only)
POST /api/admin/users/456/ban
Header: Authorization: Bearer {admin-token}

# Delete item (Admin/Moderator)
DELETE /api/admin/items/123
Header: Authorization: Bearer {admin-token}
```

---

## 🔌 WebSocket Events

### Client → Server
```javascript
// Authenticate
{ "type": "authenticate", "token": "jwt-token" }

// Subscribe to item
{ "type": "subscribe", "itemId": 123 }

// Unsubscribe
{ "type": "unsubscribe", "itemId": 123 }
```

### Server → Client
```javascript
// New bid placed
{ "type": "new_bid", "itemId": 123, "currentPrice": 150, "bidder": "John" }

// User was outbid
{ "type": "outbid", "itemId": 123, "currentPrice": 160 }

// Auction ending soon
{ "type": "auction_ending", "itemId": 123, "timeRemaining": 300 }

// Auction ended
{ "type": "auction_ended", "itemId": 123, "winner": "John", "finalPrice": 200 }
```

---

## 💾 Database Tables

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| users | User accounts | id, email, password, role, status |
| items | Auction listings | id, seller_id, title, current_price, reserve_price, status |
| bids | Bid history | id, item_id, bidder_id, amount |
| transactions | Completed sales | id, item_id, buyer_id, seller_id, amount, commission |
| item_images | Item photos | id, item_id, image_path, thumbnail_path |
| reviews | User ratings | id, reviewer_id, reviewed_user_id, rating, comment |
| watchlist | User favorites | id, user_id, item_id |
| notifications | Queued alerts | id, user_id, event_type, event_data, delivered |

---

## 🛠️ Common Tasks

### Create Admin User
```sql
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Admin User',
    'admin@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    NOW()
);
```

### Change User Role
```sql
UPDATE users SET role = 'seller' WHERE email = 'user@example.com';
```

### Reset User Password
```php
<?php
// Generate new hash
$newHash = password_hash('new_password', PASSWORD_DEFAULT);
echo $newHash;
?>
```
```sql
-- Update in database
UPDATE users SET password = 'generated_hash' WHERE email = 'user@example.com';
```

### View Platform Statistics
```sql
-- Total users by role
SELECT role, COUNT(*) as count FROM users GROUP BY role;

-- Total items by status
SELECT status, COUNT(*) as count FROM items GROUP BY status;

-- Total transactions
SELECT COUNT(*) as total, SUM(amount) as revenue FROM transactions;

-- Platform earnings
SELECT SUM(commission) as earnings FROM transactions;
```

### Clean Up Old Data
```sql
-- Delete expired items older than 30 days
DELETE FROM items 
WHERE status = 'expired' 
AND end_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Delete old notifications
DELETE FROM notifications 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

## 🔒 Security Checklist

### Before Production
- [ ] Change all default passwords
- [ ] Set strong JWT secret in .env
- [ ] Enable HTTPS
- [ ] Set APP_DEBUG=false
- [ ] Configure CORS properly
- [ ] Set up firewall rules
- [ ] Enable rate limiting
- [ ] Set up SSL certificates
- [ ] Configure secure session settings
- [ ] Review file permissions
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Test all security features

---

## 🐛 Troubleshooting

### API Returns 500 Error
```bash
# Check PHP error log
tail -f /var/log/php/error.log

# Check application log
tail -f storage/logs/app.log

# Enable debug mode temporarily
# In .env: APP_DEBUG=true
```

### Database Connection Failed
```bash
# Test MySQL connection
mysql -u root -p

# Check credentials in .env
DB_HOST=localhost
DB_NAME=auction_portal
DB_USER=root
DB_PASS=your_password
```

### JWT Token Invalid
```bash
# Verify JWT secret is set in .env
JWT_SECRET=your-secret-key-here

# Check token expiration (default 24 hours)
JWT_EXPIRATION=86400
```

### Images Not Uploading
```bash
# Check upload directory permissions
chmod 755 uploads
chmod 755 uploads/thumbnails

# Check PHP upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Increase limits in php.ini if needed
upload_max_filesize = 10M
post_max_size = 10M
```

### WebSocket Not Connecting
```bash
# Check if WebSocket server is running
ps aux | grep websocket

# Start WebSocket server
php bin/websocket-server.php

# Check port is not in use
netstat -an | grep 8080
```

### Admin Dashboard Not Loading
```bash
# Check session configuration
php -i | grep session

# Clear browser cache and cookies

# Verify admin user exists
mysql -u root -p auction_portal -e "SELECT * FROM users WHERE role='admin'"
```

---

## 📊 Performance Tips

### Enable PHP OPcache
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

### Optimize MySQL
```sql
-- Add indexes
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_end_time ON items(end_time);
CREATE INDEX idx_bids_item_id ON bids(item_id);
```

### Enable Caching
```php
// Use Redis for caching
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->setex('key', 300, 'value'); // Cache for 5 minutes
```

---

## 📞 Support Resources

- **Main Documentation:** README.md
- **System Overview:** SYSTEM_OVERVIEW.md
- **API Reference:** API_ENDPOINTS.md
- **Setup Guide:** SETUP_GUIDE.md
- **Admin Guide:** ADMIN_DASHBOARD_SETUP.md

---

## 🎯 Quick Commands

```bash
# Start API server
composer start

# Run tests
composer test

# Start WebSocket server
php bin/websocket-server.php

# Run migrations
php database/migrate.php

# Create admin user
mysql -u root -p auction_portal < database/create_admin_user.sql

# Check system status
curl http://localhost:8000/health
```

---

**Last Updated:** February 2, 2026  
**Version:** 1.0.0  
**Status:** Production Ready ✅
