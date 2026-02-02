<?php

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Config\Database;

/**
 * Property-Based Tests for User Service
 * 
 * These tests validate universal properties that should hold true
 * across many randomly generated inputs.
 */
class UserPropertiesTest extends TestCase
{
    private UserService $userService;
    private array $testUsers = [];

    protected function setUp(): void
    {
        $this->userService = new UserService();
    }

    protected function tearDown(): void
    {
        if (!empty($this->testUsers)) {
            $db = Database::getConnection();
            $ids = implode(',', $this->testUsers);
            $db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }

    /**
     * Property 1: Valid registration creates unique user accounts
     * 
     * For any valid registration data (unique email, password ≥8 chars, non-empty name),
     * registering a user should create a new user account with a unique identifier
     * and return a valid JWT token.
     * 
     * @test
     */
    public function property1_ValidRegistrationCreatesUniqueUserAccounts()
    {
        $iterations = 100;
        $createdUserIds = [];

        for ($i = 0; $i < $iterations; $i++) {
            // Generate random valid input
            $email = $this->generateRandomEmail();
            $password = $this->generateRandomPassword();
            $name = $this->generateRandomName();

            try {
                $result = $this->userService->registerUser($email, $password, $name);

                // Property assertions
                $this->assertArrayHasKey('userId', $result, "User ID should be present");
                $this->assertArrayHasKey('email', $result, "Email should be present");
                $this->assertArrayHasKey('name', $result, "Name should be present");
                $this->assertArrayHasKey('token', $result, "Token should be present");
                
                // User ID should be unique
                $this->assertNotContains($result['userId'], $createdUserIds, "User ID should be unique");
                $createdUserIds[] = $result['userId'];
                
                // Email should match input
                $this->assertEquals($email, $result['email'], "Email should match input");
                
                // Name should match input
                $this->assertEquals($name, $result['name'], "Name should match input");
                
                // Token should be non-empty string
                $this->assertIsString($result['token'], "Token should be a string");
                $this->assertNotEmpty($result['token'], "Token should not be empty");
                
                $this->testUsers[] = $result['userId'];
            } catch (\Exception $e) {
                $this->fail("Valid registration should not throw exception: " . $e->getMessage());
            }
        }

        // Verify all user IDs are unique
        $this->assertCount($iterations, array_unique($createdUserIds), "All user IDs should be unique");
    }

    // Helper methods for generating random test data

    private function generateRandomEmail(): string
    {
        $domains = ['example.com', 'test.com', 'demo.com', 'sample.org'];
        $prefix = 'user_' . time() . '_' . rand(1000, 9999);
        $domain = $domains[array_rand($domains)];
        return $prefix . '@' . $domain;
    }

    private function generateRandomPassword(): string
    {
        $length = rand(8, 20);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    private function generateRandomName(): string
    {
        $firstNames = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Eve', 'Frank'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
