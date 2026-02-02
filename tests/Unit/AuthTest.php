<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Utils\Auth;

class AuthTest extends TestCase
{
    public function testPasswordHashing()
    {
        $password = 'TestPassword123!';
        $hash = Auth::hashPassword($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$2y$', $hash); // bcrypt format
    }

    public function testPasswordVerification()
    {
        $password = 'TestPassword123!';
        $hash = Auth::hashPassword($password);
        
        $this->assertTrue(Auth::verifyPassword($password, $hash));
        $this->assertFalse(Auth::verifyPassword('WrongPassword', $hash));
    }

    public function testDifferentPasswordsProduceDifferentHashes()
    {
        $password1 = 'Password1';
        $password2 = 'Password2';
        
        $hash1 = Auth::hashPassword($password1);
        $hash2 = Auth::hashPassword($password2);
        
        $this->assertNotEquals($hash1, $hash2);
    }

    public function testSamePasswordProducesDifferentHashesEachTime()
    {
        $password = 'TestPassword123!';
        
        $hash1 = Auth::hashPassword($password);
        $hash2 = Auth::hashPassword($password);
        
        // Bcrypt includes random salt, so hashes should differ
        $this->assertNotEquals($hash1, $hash2);
        
        // But both should verify correctly
        $this->assertTrue(Auth::verifyPassword($password, $hash1));
        $this->assertTrue(Auth::verifyPassword($password, $hash2));
    }

    public function testTokenGeneration()
    {
        $userId = 123;
        $email = 'test@example.com';
        
        $token = Auth::generateToken($userId, $email);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token); // JWT format
    }

    public function testTokenVerification()
    {
        $userId = 123;
        $email = 'test@example.com';
        
        $token = Auth::generateToken($userId, $email);
        $payload = Auth::verifyToken($token);
        
        $this->assertIsArray($payload);
        $this->assertEquals($userId, $payload['userId']);
        $this->assertEquals($email, $payload['email']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testInvalidTokenReturnsNull()
    {
        $invalidToken = 'invalid.token.here';
        $payload = Auth::verifyToken($invalidToken);
        
        $this->assertNull($payload);
    }

    public function testEmptyTokenReturnsNull()
    {
        $payload = Auth::verifyToken('');
        
        $this->assertNull($payload);
    }

    public function testTokenExpirationIsSet()
    {
        $userId = 123;
        $email = 'test@example.com';
        
        $token = Auth::generateToken($userId, $email);
        $payload = Auth::verifyToken($token);
        
        $this->assertGreaterThan(time(), $payload['exp']);
        $this->assertLessThanOrEqual(time() + 604800, $payload['exp']); // 7 days
    }
}
