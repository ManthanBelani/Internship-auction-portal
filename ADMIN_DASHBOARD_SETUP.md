# Admin Dashboard Setup Guide

Complete guide to set up and use the Admin Dashboard for the Auction Portal.

## Quick Start

### 1. Create Admin User

First, create an admin user in your database. Run this SQL command in phpMyAdmin or MySQL:

```sql
-- Create admin user with default password (admin123)
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Admin User',
    'admin@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    NOW()
);

-- Create moderator user (optional)
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Moderator User',
    'moderator@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'moderator',
    'active',
    NOW()
);
```

**Default Password:** `admin123` (⚠️ Change this immediately after first login!)

### 2. Access the Dashboard

1. Start XAMPP (Apache + MySQL)
2. Ensure your backend is accessible at `http://localhost/`
3. Open browser and navigate to: `http://localhost/admin/login.php`
4. Login with:
   - Email: `admin@auction.com`
   - Password: `admin123`

### 3. Change Default Password

After first login, update your password:

```sql
-- Generate new password hash in PHP
-- Run this in a PHP file or use online bcrypt generator
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>

-- Then update in database
UPDATE users 
SET password = 'your_generated_hash' 
WHERE email = 'admin@auction.com';
```

## Dashboard Features

### Dashboard Page (index.php)
- **Statistics Cards:** Total users, items, transactions, earnings
- **Charts:** Users by role, Items by status
- **Recent Activity:** Latest system events
- **Auto-refresh:** Statistics update every 30 seconds

### User Management (users.php)
**Filters:**
- Role: Admin, Moderator, Seller, Buyer
- Status: Active, Suspended, Banned
- Search: By name or email

**Admin Actions:**
- Change user roles
- Suspend users (with optional end date)
- Ban users permanently
- Reactivate suspended/banned users

**Moderator Actions:**
- Suspend users
- Reactivate users
- (Cannot change roles or ban)

### Item Management (items.php)
**Filters:**
- Status: Active, Sold, Expired
- Search: By title

**Actions:**
- View item details
- Delete items (Admin/Moderator only)

## Role Permissions

### Admin (Full Access)
✅ View all statistics including earnings
✅ Manage all users
✅ Change user roles
✅ Suspend/ban/reactivate users
✅ Delete items
✅ Access all dashboard pages

### Moderator (Limited Access)
✅ View statistics (except earnings)
✅ View all users
✅ Suspend/reactivate users
✅ Delete items
❌ Cannot change user roles
❌ Cannot ban users
❌ Cannot view earnings page

### Seller/Buyer
❌ Cannot access admin dashboard

## File Structure

```
admin/
├── login.php              # Login page
├── logout.php             # Logout handler
├── index.php              # Dashboard (statistics)
├── users.php              # User management
├── items.php              # Item management
├── includes/
│   ├── header.php         # Common header with navbar
│   ├── sidebar.php        # Navigation menu
│   └── footer.php         # Common footer
├── assets/
│   ├── css/
│   │   ├── style.css      # Main dashboard styles
│   │   └── login.css      # Login page styles
│   └── js/
│       ├── main.js        # Common utilities
│       ├── login.js       # Login functionality
│       ├── dashboard.js   # Dashboard statistics loader
│       ├── users.js       # User management functions
│       └── items.js       # Item management functions
└── README.md              # Dashboard documentation
```

## API Endpoints Used

The dashboard communicates with these backend endpoints:

```
Authentication:
POST   /api/users/login

Statistics:
GET    /api/admin/stats

User Management:
GET    /api/admin/users
PUT    /api/admin/users/{id}/role
POST   /api/admin/users/{id}/suspend
POST   /api/admin/users/{id}/ban
POST   /api/admin/users/{id}/reactivate

Item Management:
GET    /api/items
DELETE /api/admin/items/{id}
```

## Common Tasks

### Suspend a User

1. Go to **Users** page
2. Use filters to find the user
3. Click **Suspend** button
4. Enter suspension end date (optional):
   - Format: `YYYY-MM-DD HH:MM:SS`
   - Example: `2024-12-31 23:59:59`
   - Leave empty for indefinite suspension
5. Confirm action

### Change User Role

1. Go to **Users** page (Admin only)
2. Find the user
3. Click **Role** button
4. Enter new role: `buyer`, `seller`, `moderator`, or `admin`
5. Confirm action

### Delete an Item

1. Go to **Auction Items** page
2. Use filters to find the item
3. Click **Delete** button
4. Confirm deletion (⚠️ This cannot be undone!)

### Ban a User

1. Go to **Users** page (Admin only)
2. Find the user
3. Click **Ban** button
4. Confirm action (⚠️ Permanent action!)

### Reactivate a User

1. Go to **Users** page
2. Filter by status: **Suspended** or **Banned**
3. Find the user
4. Click **Reactivate** button
5. User status will change to **Active**

## Troubleshooting

### Cannot Login

**Problem:** "Invalid email or password" error

**Solutions:**
1. Verify user exists in database
2. Check password hash is correct
3. Ensure user role is `admin` or `moderator`
4. Check user status is `active`

```sql
-- Verify user
SELECT id, name, email, role, status FROM users WHERE email = 'admin@auction.com';
```

### "You don't have permission" Error

**Problem:** User cannot access admin panel

**Solutions:**
1. Check user role in database
2. Ensure role is `admin` or `moderator`
3. Update role if needed:

```sql
UPDATE users SET role = 'admin' WHERE email = 'admin@auction.com';
```

### Statistics Not Loading

**Problem:** Dashboard shows "Loading..." indefinitely

**Solutions:**
1. Check browser console for errors (F12)
2. Verify backend API is running
3. Check API token in session:
   - View page source
   - Look for `<meta name="api-token" content="...">`
4. Test API endpoint manually:
   ```
   http://localhost/api/admin/stats
   ```

### CORS Errors

**Problem:** API requests blocked by CORS policy

**Solution:** Ensure CORS headers are set in `public/index.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Session Expired

**Problem:** Redirected to login after some time

**Solution:** This is normal behavior. Sessions expire for security. Simply login again.

## Security Best Practices

1. **Change Default Password**
   - Never use `admin123` in production
   - Use strong passwords (12+ characters)

2. **Limit Admin Accounts**
   - Only create admin accounts when necessary
   - Use moderator role for content management

3. **Regular Audits**
   - Review user list regularly
   - Check for suspicious activity
   - Monitor banned/suspended users

4. **Secure Environment**
   - Use HTTPS in production
   - Keep PHP and MySQL updated
   - Regular database backups

5. **Session Security**
   - Sessions expire automatically
   - Logout when done
   - Don't share credentials

## Customization

### Change Theme Colors

Edit `admin/assets/css/style.css`:

```css
:root {
    --primary-color: #2196F3;    /* Main blue color */
    --secondary-color: #1976D2;  /* Darker blue */
    --success-color: #4CAF50;    /* Green */
    --danger-color: #f44336;     /* Red */
    --warning-color: #FF9800;    /* Orange */
    --dark-color: #263238;       /* Dark gray */
}
```

### Add New Menu Item

Edit `admin/includes/sidebar.php`:

```php
<li class="<?php echo basename($_SERVER['PHP_SELF']) == 'mypage.php' ? 'active' : ''; ?>">
    <a href="mypage.php">
        <i class="fas fa-icon-name"></i>
        <span>My Page</span>
    </a>
</li>
```

### Add New Statistics Card

Edit `admin/index.php`:

```html
<div class="stat-card">
    <div class="stat-icon" style="background: #4CAF50;">
        <i class="fas fa-icon"></i>
    </div>
    <div class="stat-content">
        <h3 id="my-stat">0</h3>
        <p>My Statistic</p>
    </div>
</div>
```

Then update `admin/assets/js/dashboard.js` to load the data.

## Production Deployment

### Before Going Live:

1. ✅ Change all default passwords
2. ✅ Enable HTTPS
3. ✅ Set `APP_DEBUG=false` in `.env`
4. ✅ Restrict database access
5. ✅ Set up regular backups
6. ✅ Configure proper session settings
7. ✅ Review all user roles
8. ✅ Test all functionality
9. ✅ Set up monitoring/logging
10. ✅ Document admin procedures

### Environment Variables

Update `.env` file:

```env
APP_DEBUG=false
APP_ENV=production
JWT_SECRET=your-secure-random-secret-key
DB_HOST=your-production-host
DB_NAME=your-production-database
DB_USER=your-production-user
DB_PASS=your-secure-password
```

## Support

For additional help:
- Main README: `README.md`
- API Documentation: `API_ENDPOINTS.md`
- RBAC Guide: `RBAC_IMPLEMENTATION_SUMMARY.md`
- Dashboard README: `admin/README.md`

## Next Steps

1. ✅ Login to admin dashboard
2. ✅ Change default password
3. ✅ Explore dashboard features
4. ✅ Create test users with different roles
5. ✅ Test user management features
6. ✅ Test item management features
7. ✅ Customize theme (optional)
8. ✅ Add additional pages (optional)

---

**Ready to go!** Your admin dashboard is now set up and ready to use. 🎉
