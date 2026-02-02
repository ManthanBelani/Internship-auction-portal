-- Add role column to users table
ALTER TABLE users 
ADD COLUMN role ENUM('admin', 'seller', 'buyer', 'moderator') DEFAULT 'buyer' AFTER name,
ADD COLUMN status ENUM('active', 'suspended', 'banned') DEFAULT 'active' AFTER role,
ADD COLUMN suspended_until TIMESTAMP NULL AFTER status,
ADD INDEX idx_role (role),
ADD INDEX idx_status (status);

-- Create default admin user (password: admin123 - CHANGE IN PRODUCTION!)
-- Password hash for 'admin123'
INSERT INTO users (email, password_hash, name, role, registered_at) 
VALUES ('admin@auction.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin', NOW())
ON DUPLICATE KEY UPDATE role = 'admin';
