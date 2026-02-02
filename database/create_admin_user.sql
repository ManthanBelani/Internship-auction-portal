-- Create Admin User for Admin Dashboard
-- Run this script to create default admin and moderator accounts
-- ⚠️ IMPORTANT: Change passwords immediately after first login!

-- Create Admin User
-- Email: admin@auction.com
-- Password: admin123
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Admin User',
    'admin@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    status = 'active';

-- Create Moderator User (Optional)
-- Email: moderator@auction.com
-- Password: admin123
INSERT INTO users (name, email, password, role, status, created_at) 
VALUES (
    'Moderator User',
    'moderator@auction.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'moderator',
    'active',
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'moderator',
    status = 'active';

-- Verify users were created
SELECT id, name, email, role, status, created_at 
FROM users 
WHERE email IN ('admin@auction.com', 'moderator@auction.com');

-- ============================================
-- PASSWORD CHANGE INSTRUCTIONS
-- ============================================
-- After first login, generate a new password hash:
--
-- Method 1: Using PHP
-- <?php
-- echo password_hash('your_new_password', PASSWORD_DEFAULT);
-- ?>
--
-- Method 2: Using online bcrypt generator
-- Visit: https://bcrypt-generator.com/
-- Enter your password and copy the hash
--
-- Then update the password:
-- UPDATE users 
-- SET password = 'your_generated_hash' 
-- WHERE email = 'admin@auction.com';
-- ============================================

-- ============================================
-- ROLE DESCRIPTIONS
-- ============================================
-- admin:     Full system access, manage users, view earnings
-- moderator: Content moderation, suspend users, delete items
-- seller:    Create items, upload images, manage own listings
-- buyer:     Place bids, watchlist, reviews
-- ============================================

-- ============================================
-- USEFUL QUERIES
-- ============================================

-- View all admin/moderator users
-- SELECT id, name, email, role, status FROM users WHERE role IN ('admin', 'moderator');

-- Change user role
-- UPDATE users SET role = 'admin' WHERE email = 'user@example.com';

-- Activate user
-- UPDATE users SET status = 'active', suspended_until = NULL WHERE id = 1;

-- Count users by role
-- SELECT role, COUNT(*) as count FROM users GROUP BY role;

-- ============================================
