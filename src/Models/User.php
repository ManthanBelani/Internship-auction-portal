<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use PDOException;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new user
     * 
     * @param string $email User email (must be unique)
     * @param string $passwordHash Hashed password
     * @param string $name User name
     * @return array Created user data
     * @throws \Exception If email already exists or validation fails
     */
    public function create(string $email, string $passwordHash, string $name): array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Validate required fields
        if (empty($name)) {
            throw new \Exception('Name is required');
        }

        try {
            $sql = "INSERT INTO users (email, password, name, created_at) 
                    VALUES (:email, :password, :name, CURRENT_TIMESTAMP)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':password' => $passwordHash,
                ':name' => $name
            ]);

            $userId = (int)$this->db->lastInsertId();
            
            return $this->findById($userId);
        } catch (PDOException $e) {
            // Check for duplicate email error
            if ($e->getCode() == 23000) {
                throw new \Exception('Email already exists');
            }
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT id, email, password, name, role, status, suspended_until, 
                created_at, updated_at 
                FROM users WHERE email = :email";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    /**
     * Find user by ID
     * 
     * @param int $userId User ID
     * @return array User data
     * @throws \Exception If user not found
     */
    public function findById(int $userId): array
    {
        $sql = "SELECT id, email, password, name, role, status, suspended_until, 
                created_at, updated_at 
                FROM users WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        return $user;
    }

    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $data Data to update (name, email)
     * @return array Updated user data
     * @throws \Exception If user not found or validation fails
     */
    public function update(int $userId, array $data): array
    {
        // Verify user exists
        $this->findById($userId);

        $updates = [];
        $params = [':id' => $userId];

        if (isset($data['name'])) {
            if (empty($data['name'])) {
                throw new \Exception('Name cannot be empty');
            }
            $updates[] = "name = :name";
            $params[':name'] = $data['name'];
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }
            $updates[] = "email = :email";
            $params[':email'] = $data['email'];
        }

        if (empty($updates)) {
            return $this->findById($userId);
        }

        try {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->findById($userId);
        } catch (PDOException $e) {
            // Check for duplicate email error
            if ($e->getCode() == 23000) {
                throw new \Exception('Email already exists');
            }
            throw new \Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Get public profile (excludes sensitive data)
     * 
     * @param int $userId User ID
     * @return array Public user data
     * @throws \Exception If user not found
     */
    public function getPublicProfile(int $userId): array
    {
        $sql = "SELECT id, name, role, registered_at FROM users WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        return $user;
    }
    
    /**
     * Update user role (admin only)
     * 
     * @param int $userId User ID
     * @param string $role New role (admin, seller, buyer, moderator)
     * @return array Updated user data
     * @throws \Exception If invalid role or user not found
     */
    public function updateRole(int $userId, string $role): array
    {
        $validRoles = ['admin', 'seller', 'buyer', 'moderator'];
        
        if (!in_array($role, $validRoles)) {
            throw new \Exception('Invalid role. Must be: ' . implode(', ', $validRoles));
        }
        
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role' => $role, ':id' => $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new \Exception('User not found');
        }
        
        return $this->findById($userId);
    }
    
    /**
     * Suspend user account
     * 
     * @param int $userId User ID
     * @param string|null $until Suspension end date (null for indefinite)
     * @return array Updated user data
     * @throws \Exception If user not found
     */
    public function suspendUser(int $userId, ?string $until = null): array
    {
        $sql = "UPDATE users SET status = 'suspended', suspended_until = :until WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':until' => $until, ':id' => $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new \Exception('User not found');
        }
        
        return $this->findById($userId);
    }
    
    /**
     * Ban user account permanently
     * 
     * @param int $userId User ID
     * @return array Updated user data
     * @throws \Exception If user not found
     */
    public function banUser(int $userId): array
    {
        $sql = "UPDATE users SET status = 'banned', suspended_until = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new \Exception('User not found');
        }
        
        return $this->findById($userId);
    }
    
    /**
     * Reactivate user account
     * 
     * @param int $userId User ID
     * @return array Updated user data
     * @throws \Exception If user not found
     */
    public function reactivateUser(int $userId): array
    {
        $sql = "UPDATE users SET status = 'active', suspended_until = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new \Exception('User not found');
        }
        
        return $this->findById($userId);
    }
    
    /**
     * Get all users (admin only)
     * 
     * @param array $filters Optional filters (role, status, search)
     * @return array List of users
     */
    public function getAllUsers(array $filters = []): array
    {
        $sql = "SELECT id, email, name, role, status, suspended_until, registered_at 
                FROM users WHERE 1=1";
        $params = [];
        
        if (isset($filters['role'])) {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (name LIKE :search OR email LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY registered_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
