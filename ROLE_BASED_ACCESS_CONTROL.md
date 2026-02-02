# Role-Based Access Control (RBAC) System

## Overview

The Auction Portal backend now includes a comprehensive role-based access control system with four distinct roles: **Admin**, **Seller**, **Buyer**, and **Moderator**.

---

## Roles & Permissions

### 1. **Admin** 👑
**Full system access with all privileges**

**Permissions:**
- ✅ All Seller permissions
- ✅ All Buyer permissions
- ✅ All Moderator permissions
- ✅ Manage user roles (promote/demote users)
- ✅ Ban/suspend/reactivate users
- ✅ View platform statistics and earnings
- ✅ Delete any content (items, reviews, etc.)
- ✅ Override auction rules
- ✅ Access admin dashboard

**Use Cases:**
- Platform owner
- System administrator
- Technical support lead

---

### 2. **Seller** 🏪
**Can create and manage auction listings**

**Permissions:**
- ✅ Create auction items
- ✅ Upload images for their items
- ✅ Set reserve prices
- ✅ Set custom commission rates (if allowed)
- ✅ View their sales history
- ✅ View their earnings (after commission)
- ✅ Edit/delete their own items
- ✅ All Buyer permissions (can also buy)

**Restrictions:**
- ❌ Cannot modify other users' items
- ❌ Cannot bid on their own items
- ❌ Cannot access admin features

**Use Cases:**
- Individual sellers
- Small businesses
- Resellers

---

### 3. **Buyer** 🛒
**Can participate in auctions as bidders**

**Permissions:**
- ✅ Browse all active auctions
- ✅ Place bids on items
- ✅ Add items to watchlist
- ✅ Leave reviews after purchases
- ✅ View transaction history
- ✅ Update their profile
- ✅ Receive real-time bid notifications

**Restrictions:**
- ❌ Cannot create auction items
- ❌ Cannot upload images
- ❌ Cannot set reserve prices
- ❌ Cannot access seller features

**Use Cases:**
- Regular customers
- Collectors
- Bargain hunters

---

### 4. **Moderator** 🛡️
**Content oversight and community management**

**Permissions:**
- ✅ Review flagged items and users
- ✅ Suspend users temporarily
- ✅ Delete inappropriate content
- ✅ Suspend auctions temporarily
- ✅ View user reports
- ✅ Access moderation dashboard

**Restrictions:**
- ❌ Cannot access financial data
- ❌ Cannot permanently ban users (only suspend)
- ❌ Cannot change user roles
- ❌ Cannot view platform earnings
- ❌ Cannot delete users

**Use Cases:**
- Community managers
- Content moderators
- Customer support staff

---

## Database Schema

### Users Table Extension

```sql
ALTER TABLE users 
ADD COLUMN role ENUM('admin', 'seller', 'buyer', 'moderator') DEFAULT 'buyer',
ADD COLUMN status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
ADD COLUMN suspended_until TIMESTAMP NULL;
```

**Fields:**
- `role`: User's role in the system
- `status`: Account status (active/suspended/banned)
- `suspended_until`: Suspension end date (NULL for indefinite or not suspended)

---

## API Endpoints

### Admin Endpoints

#### Get All Users
```http
GET /api/admin/users
Authorization: Bearer {admin_token}
Query Parameters:
  - role: Filter by role (admin, seller, buyer, moderator)
  - status: Filter by status (active, suspended, banned)
  - search: Search by name or email
```

**Response:**
```json
{
  "users": [
    {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "role": "seller",
      "status": "active",
      "suspended_until": null,
      "registered_at": "2024-01-01 10:00:00"
    }
  ],
  "total": 1
}
```

#### Update User Role
```http
PUT /api/admin/users/{userId}/role
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "role": "seller"
}
```

#### Suspend User
```http
POST /api/admin/users/{userId}/suspend
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "until": "2024-12-31 23:59:59"  // Optional, null for indefinite
}
```

#### Ban User
```http
POST /api/admin/users/{userId}/ban
Authorization: Bearer {admin_token}
```

#### Reactivate User
```http
POST /api/admin/users/{userId}/reactivate
Authorization: Bearer {admin_token}
```

#### Get Platform Statistics
```http
GET /api/admin/stats
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "users": {
    "byRole": [
      {"role": "buyer", "count": 150},
      {"role": "seller", "count": 45},
      {"role": "admin", "count": 2},
      {"role": "moderator", "count": 5}
    ],
    "total": 202
  },
  "items": {
    "byStatus": [
      {"status": "active", "count": 50},
      {"status": "completed", "count": 120},
      {"status": "expired", "count": 30}
    ],
    "total": 200
  },
  "transactions": {
    "total": 120
  },
  "earnings": {
    "total": 1250.50
  }
}
```

#### Delete Item (Admin/Moderator)
```http
DELETE /api/admin/items/{itemId}
Authorization: Bearer {admin_token}
```

---

## Middleware Usage

### RoleMiddleware Class

```php
use App\Middleware\RoleMiddleware;

// Require admin role
$admin = RoleMiddleware::requireAdmin();
if (!$admin) return;

// Require admin or moderator
$user = RoleMiddleware::requireAdminOrModerator();
if (!$user) return;

// Require seller (or admin)
$seller = RoleMiddleware::requireSeller();
if (!$seller) return;

// Require buyer (buyer, seller, or admin)
$buyer = RoleMiddleware::requireBuyer();
if (!$buyer) return;

// Check specific roles
$user = RoleMiddleware::checkRole(['admin', 'moderator']);
if (!$user) return;

// Check resource ownership
$canModify = RoleMiddleware::canModifyResource(
    $resourceOwnerId,
    $userId,
    $userRole
);

// Check moderation permission
$canModerate = RoleMiddleware::canModerate($userRole);
```

---

## Implementation Examples

### 1. Protecting Item Creation (Sellers Only)

```php
// In ItemController
public function create(array $data): void
{
    // Only sellers and admins can create items
    $user = RoleMiddleware::requireSeller();
    if (!$user) return;
    
    // Create item logic...
}
```

### 2. Admin Dashboard Access

```php
// In AdminController
public function getDashboard(): void
{
    $admin = RoleMiddleware::requireAdmin();
    if (!$admin) return;
    
    // Show admin dashboard...
}
```

### 3. Moderator Content Review

```php
// In ModerationController
public function reviewContent(int $itemId): void
{
    $user = RoleMiddleware::requireAdminOrModerator();
    if (!$user) return;
    
    // Review content logic...
}
```

---

## Default Admin Account

**Email:** `admin@auction.com`  
**Password:** `admin123`  
**Role:** `admin`

⚠️ **IMPORTANT:** Change this password immediately in production!

```php
// Change admin password
$userService = new UserService();
$userService->updatePassword(1, 'new_secure_password');
```

---

## User Registration with Roles

### Register as Buyer (Default)
```http
POST /api/users/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepass123"
}
```

### Register as Seller
```http
POST /api/users/register
Content-Type: application/json

{
  "name": "Jane Seller",
  "email": "jane@example.com",
  "password": "securepass123",
  "role": "seller"
}
```

**Note:** Only admins can create admin or moderator accounts via role update endpoint.

---

## Role Upgrade Flow

### Buyer → Seller Upgrade

1. User requests seller status
2. Admin reviews request
3. Admin updates role:
```http
PUT /api/admin/users/{userId}/role
{
  "role": "seller"
}
```

---

## Account Status Management

### Status Types

1. **Active** - Normal account, full access
2. **Suspended** - Temporary restriction
   - Can be time-limited or indefinite
   - User cannot login or perform actions
   - Can be reactivated
3. **Banned** - Permanent restriction
   - User cannot login
   - Requires admin to reactivate

### Suspension Workflow

```php
// Suspend for 7 days
$suspendUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
$userModel->suspendUser($userId, $suspendUntil);

// Indefinite suspension
$userModel->suspendUser($userId, null);

// Reactivate
$userModel->reactivateUser($userId);
```

---

## Security Considerations

### 1. Token Validation
- All protected endpoints verify JWT token
- Token includes user ID and email
- Role is fetched from database (not stored in token)

### 2. Status Checks
- Suspended/banned users cannot access any endpoints
- Status checked on every authenticated request

### 3. Permission Hierarchy
```
Admin > Moderator > Seller > Buyer
```

### 4. Resource Ownership
- Users can only modify their own resources
- Admins can modify any resource
- Moderators can delete content but not modify

---

## Flutter Integration

### Update User Model

```dart
class User {
  final int userId;
  final String name;
  final String email;
  final String role;  // NEW
  final String status;  // NEW
  final String? suspendedUntil;  // NEW
  // ... other fields
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      userId: json['userId'],
      name: json['name'],
      email: json['email'],
      role: json['role'] ?? 'buyer',
      status: json['status'] ?? 'active',
      suspendedUntil: json['suspendedUntil'],
      // ... other fields
    );
  }
}
```

### Role-Based UI

```dart
// Show admin panel only to admins
if (user.role == 'admin') {
  return AdminDashboard();
}

// Show create item button only to sellers
if (user.role == 'seller' || user.role == 'admin') {
  FloatingActionButton(
    onPressed: () => Navigator.push(...),
    child: Icon(Icons.add),
  );
}

// Show moderation tools
if (user.role == 'admin' || user.role == 'moderator') {
  return ModerationPanel();
}
```

### Admin Service

```dart
class AdminService extends ApiService {
  Future<List<User>> getAllUsers({String? role, String? status}) async {
    String url = '${ApiConfig.apiBaseUrl}/admin/users';
    List<String> params = [];
    
    if (role != null) params.add('role=$role');
    if (status != null) params.add('status=$status');
    
    if (params.isNotEmpty) url += '?${params.join('&')}';
    
    final response = await get(url, requiresAuth: true);
    return (response['users'] as List)
        .map((u) => User.fromJson(u))
        .toList();
  }
  
  Future<void> updateUserRole(int userId, String role) async {
    await put(
      '${ApiConfig.apiBaseUrl}/admin/users/$userId/role',
      {'role': role},
      requiresAuth: true,
    );
  }
  
  Future<void> suspendUser(int userId, {String? until}) async {
    await post(
      '${ApiConfig.apiBaseUrl}/admin/users/$userId/suspend',
      {'until': until},
      requiresAuth: true,
    );
  }
}
```

---

## Testing

### Test Admin Access
```bash
# Login as admin
curl -X POST http://localhost/api/users/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@auction.com","password":"admin123"}'

# Get all users
curl -X GET http://localhost/api/admin/users \
  -H "Authorization: Bearer {token}"
```

### Test Role Restrictions
```bash
# Try to access admin endpoint as buyer (should fail)
curl -X GET http://localhost/api/admin/users \
  -H "Authorization: Bearer {buyer_token}"
# Expected: 403 Forbidden
```

---

## Migration Instructions

### 1. Run Migration
```bash
php database/run_role_migration.php
```

### 2. Verify Tables
```sql
DESCRIBE users;
-- Should show: role, status, suspended_until columns
```

### 3. Test Default Admin
```bash
# Login with default admin
curl -X POST http://localhost/api/users/login \
  -d '{"email":"admin@auction.com","password":"admin123"}'
```

### 4. Change Admin Password
```php
// In your code or via API
$userService->updatePassword(1, 'new_secure_password');
```

---

## Best Practices

1. **Always check role before sensitive operations**
2. **Use middleware consistently across all protected endpoints**
3. **Log admin actions for audit trail**
4. **Implement rate limiting for admin endpoints**
5. **Require 2FA for admin accounts in production**
6. **Regularly review user roles and permissions**
7. **Monitor suspended/banned accounts**

---

**Last Updated:** February 2026  
**Version:** 1.0.0
