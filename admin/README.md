# Admin Dashboard - Auction Portal

A comprehensive admin dashboard for managing the Auction Portal backend system.

## Features

### 1. Dashboard (index.php)
- Real-time statistics display
- User count by role (Admin, Moderator, Seller, Buyer)
- Item count by status (Active, Sold, Expired)
- Total transactions count
- Platform earnings overview
- Interactive charts (Chart.js)
- Recent activity feed

### 2. User Management (users.php)
- View all users with filtering options
- Filter by role (Admin, Moderator, Seller, Buyer)
- Filter by status (Active, Suspended, Banned)
- Search by name or email
- **Admin Actions:**
  - Change user roles
  - Suspend users (temporary or indefinite)
  - Ban users permanently
  - Reactivate suspended/banned users
- **Moderator Actions:**
  - Suspend users
  - Reactivate users

### 3. Item Management (items.php)
- View all auction items
- Filter by status (Active, Sold, Expired)
- Search by title
- View item details
- Delete items (Admin/Moderator only)

### 4. Authentication
- Secure login system
- Role-based access control
- Session management
- JWT token integration with backend API

## Access Levels

### Admin
- Full system access
- Manage all users and roles
- View platform earnings
- Ban/suspend users
- Delete content
- Access all dashboard features

### Moderator
- Content moderation
- Suspend/reactivate users
- Delete inappropriate items
- View statistics (limited)
- Cannot access earnings or change roles

## Installation & Setup

### 1. Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Backend API running (see main README.md)
- Database migrations completed

### 2. File Structure
```
admin/
├── index.php              # Dashboard
├── login.php              # Login page
├── logout.php             # Logout handler
├── users.php              # User management
├── items.php              # Item management
├── includes/
│   ├── header.php         # Common header
│   ├── sidebar.php        # Navigation sidebar
│   └── footer.php         # Common footer
└── assets/
    ├── css/
    │   ├── style.css      # Main styles
    │   └── login.css      # Login page styles
    └── js/
        ├── main.js        # Common utilities
        ├── login.js       # Login functionality
        ├── dashboard.js   # Dashboard statistics
        ├── users.js       # User management
        └── items.js       # Item management
```

### 3. Access the Dashboard

1. Start XAMPP (Apache + MySQL)
2. Ensure backend API is running
3. Navigate to: `http://localhost/admin/login.php`
4. Login with admin credentials:
   - Email: `admin@auction.com`
   - Password: `admin123`

**⚠️ IMPORTANT: Change default password after first login!**

## Default Credentials

Create an admin user in your database:

```sql
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Admin User',
    'admin@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'admin',
    'active',
    NOW()
);
```

## API Integration

The dashboard communicates with the backend API using the following endpoints:

### Statistics
- `GET /api/admin/stats` - Get platform statistics

### User Management
- `GET /api/admin/users` - Get all users (with filters)
- `PUT /api/admin/users/{id}/role` - Update user role
- `POST /api/admin/users/{id}/suspend` - Suspend user
- `POST /api/admin/users/{id}/ban` - Ban user
- `POST /api/admin/users/{id}/reactivate` - Reactivate user

### Item Management
- `GET /api/items` - Get all items (with filters)
- `DELETE /api/admin/items/{id}` - Delete item

### Authentication
- `POST /api/users/login` - Login endpoint

## Security Features

1. **Session Management**
   - PHP sessions for authentication state
   - Automatic session expiration
   - Secure session cookies

2. **Role-Based Access Control**
   - Middleware checks on every page
   - Role verification before API calls
   - UI elements hidden based on permissions

3. **JWT Token Integration**
   - Token stored in session
   - Passed to API via Authorization header
   - Automatic token validation

4. **Input Validation**
   - Client-side validation
   - Server-side validation via API
   - XSS protection (htmlspecialchars)

## Customization

### Adding New Pages

1. Create new PHP file in `admin/` directory
2. Include authentication check:
```php
<?php
session_start();
if (!isset($_SESSION['admin_user'])) {
    header('Location: login.php');
    exit;
}
?>
```

3. Include header and sidebar:
```php
<?php
$pageTitle = 'Your Page Title';
$apiToken = $_SESSION['admin_user']['token'] ?? '';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
```

4. Add your content and include footer:
```php
<?php include 'includes/footer.php'; ?>
```

5. Update sidebar menu in `includes/sidebar.php`

### Styling

- Main styles: `assets/css/style.css`
- CSS variables for easy theming (see `:root` section)
- Responsive design with mobile breakpoints

### JavaScript Utilities

Available in `assets/js/main.js`:
- `apiCall(endpoint, options)` - Make authenticated API calls
- `showToast(message, type)` - Show notifications
- `confirmAction(message)` - Confirmation dialogs
- `formatCurrency(amount)` - Format currency
- `formatDate(dateString)` - Format dates
- `formatRelativeTime(dateString)` - Relative time (e.g., "2 hours ago")

## Troubleshooting

### Login Issues
- Verify backend API is running
- Check database connection
- Ensure user has admin/moderator role
- Clear browser cache and cookies

### API Errors
- Check browser console for errors
- Verify API token in session
- Ensure CORS is enabled on backend
- Check API endpoint URLs

### Statistics Not Loading
- Verify admin API routes are registered
- Check database has data
- Inspect network tab for failed requests
- Ensure user has admin role for earnings

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (responsive design)

## Dependencies

### CSS
- Font Awesome 6.4.0 (icons)

### JavaScript
- Chart.js (statistics charts)
- Vanilla JavaScript (no jQuery required)

## Future Enhancements

- [ ] Real-time notifications via WebSocket
- [ ] Advanced analytics and reports
- [ ] Bulk user actions
- [ ] Export data to CSV/Excel
- [ ] Email notifications
- [ ] Activity logs and audit trail
- [ ] Dark mode theme
- [ ] Multi-language support

## Support

For issues or questions:
1. Check the main project README.md
2. Review API_ENDPOINTS.md for API documentation
3. Check RBAC_IMPLEMENTATION_SUMMARY.md for role details

## License

Part of the Auction Portal Backend project.
