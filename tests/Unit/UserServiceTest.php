<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Config\Database;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private array $testUsers = [];

    protected function setUp(): void
    {
        $this->userService = new UserService();
    }

    protected function tearDown(): void
    {
        // Clean up test users
        if (!empty($this->testUsers)) {
            $db = Database::getConnection();
            $ids = implode(',', $this->testUsers);
            $db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }

    public function testUserRegistration()
    {
        $email = 'test_' . time() . '@example.com';
        $password = 'Password123!';
        $name = 'Test User';

        $result = $this->userService->registerUser($email, $password, $name);

        $this->assertArrayHasKey('userId', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($name, $result['name']);

        $this->testUsers[] = $result['userId'];
    }

    public function testDuplicateEmailRejection()
    {
        $email = 'duplicate_' . time() . '@example.com';
        $password = 'Password123!';

        $user1 = $this->userService->registerUser($email, $password, 'User 1');
        $this->testUsers[] = $user1['userId'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email already exists');
        
        $this->userService->registerUser($email, $password, 'User 2');
    }

    public function testInvalidEmailFormat()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid email format');
        
        $this->userService->registerUser('invalid-email', 'Password123!', 'Test User');
    }

    public function testShortPasswordRejection()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('at least 8 characters');
        
        $this->userService->registerUser('test@example.com', 'short', 'Test User');
    }

    public function testEmptyNameRejection()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Name is required');
        
        $this->userService->registerUser('test@example.com', 'Password123!', '');
    }

    public function testUserAuthentication()
    {
        $email = 'auth_' . time() . '@example.com';
        $password = 'Password123!';

        $registered = $this->userService->registerUser($email, $password, 'Auth User');
        $this->testUsers[] = $registered['userId'];

        $authenticated = $this->userService->authenticateUser($email, $password);

        $this->assertEquals($registered['userId'], $authenticated['userId']);
        $this->assertEquals($email, $authenticated['email']);
        $this->assertArrayHasKey('token', $authenticated);
    }

    public function testInvalidCredentials()
    {
        $email = 'invalid_' . time() . '@example.com';
        $password = 'Password123!';

        $registered = $this->userService->registerUser($email, $password, 'Invalid User');
        $this->testUsers[] = $registered['userId'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');
        
        $this->userService->authenticateUser($email, 'WrongPassword');
    }

    public function testNonExistentUserAuthentication()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');
        
        $this->userService->authenticateUser('nonexistent@example.com', 'Password123!');
    }

    public function testGetUserProfile()
    {
        $email = 'profile_' . time() . '@example.com';
        $user = $this->userService->registerUser($email, 'Password123!', 'Profile User');
        $this->testUsers[] = $user['userId'];

        $profile = $this->userService->getUserProfile($user['userId']);

        $this->assertEquals($user['userId'], $profile['userId']);
        $this->assertEquals($email, $profile['email']);
        $this->assertArrayHasKey('registeredAt', $profile);
    }

    public function testUpdateUserProfile()
    {
        $email = 'update_' . time() . '@example.com';
        $user = $this->userService->registerUser($email, 'Password123!', 'Original Name');
        $this->testUsers[] = $user['userId'];

        $updated = $this->userService->updateUserProfile(
            $user['userId'],
            $user['userId'],
            ['name' => 'Updated Name']
        );

        $this->assertEquals('Updated Name', $updated['name']);
    }

    public function testUnauthorizedProfileModification()
    {
        $user1 = $this->userService->registerUser('user1_' . time() . '@example.com', 'Password123!', 'User 1');
        $this->testUsers[] = $user1['userId'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot modify another user');
        
        $this->userService->updateUserProfile($user1['userId'], 999, ['name' => 'Hacker']);
    }

    public function testGetPublicProfile()
    {
        $email = 'public_' . time() . '@example.com';
        $user = $this->userService->registerUser($email, 'Password123!', 'Public User');
        $this->testUsers[] = $user['userId'];

        $publicProfile = $this->userService->getPublicProfile($user['userId']);

        $this->assertArrayHasKey('userId', $publicProfile);
        $this->assertArrayHasKey('name', $publicProfile);
        $this->assertArrayHasKey('registeredAt', $publicProfile);
        $this->assertArrayNotHasKey('email', $publicProfile);
        $this->assertArrayNotHasKey('password', $publicProfile);
    }
}
