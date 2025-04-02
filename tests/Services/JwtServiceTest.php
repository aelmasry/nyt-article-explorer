<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\JwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtServiceTest extends TestCase
{
    private $jwtService;
    private $testSecret = 'test_secret_key';
    private $testExpiry = 3600;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up environment variables
        $_ENV['JWT_SECRET'] = $this->testSecret;
        $_ENV['JWT_EXPIRY'] = $this->testExpiry;
        
        $this->jwtService = new JwtService();
    }

    public function testGenerateToken()
    {
        $payload = [
            'user_id' => 123,
            'email' => 'test@example.com'
        ];

        $token = $this->jwtService->generateToken($payload);
        
        // Verify token structure
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Decode token to verify payload
        $decoded = JWT::decode($token, new Key($this->testSecret, 'HS256'));
        $this->assertEquals($payload['user_id'], $decoded->user_id);
        $this->assertEquals($payload['email'], $decoded->email);
        $this->assertIsInt($decoded->iat);
        $this->assertIsInt($decoded->exp);
        $this->assertEquals($decoded->exp, $decoded->iat + $this->testExpiry);
    }

    public function testValidateToken()
    {
        $payload = [
            'user_id' => 123,
            'email' => 'test@example.com'
        ];

        $token = $this->jwtService->generateToken($payload);
        
        // Test valid token
        $result = $this->jwtService->validateToken($token);
        $this->assertIsArray($result);
        $this->assertEquals($payload['user_id'], $result['user_id']);
        $this->assertEquals($payload['email'], $result['email']);
        
        // Test invalid token
        $invalidToken = 'invalid.token.string';
        $result = $this->jwtService->validateToken($invalidToken);
        $this->assertNull($result);
        
        // Test expired token
        $_ENV['JWT_EXPIRY'] = -1; // Set expiry to past
        $expiredToken = $this->jwtService->generateToken($payload);
        $_ENV['JWT_EXPIRY'] = $this->testExpiry; // Reset expiry
        $result = $this->jwtService->validateToken($expiredToken);
        $this->assertNull($result);
    }

    public function testGetTokenFromHeader()
    {
        // Test with valid Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test.token.here';
        $result = $this->jwtService->getTokenFromHeader();
        $this->assertEquals('test.token.here', $result);
        
        // Test with invalid Authorization header format
        $_SERVER['HTTP_AUTHORIZATION'] = 'InvalidFormat test.token.here';
        $result = $this->jwtService->getTokenFromHeader();
        $this->assertNull($result);
        
        // Test with missing Authorization header
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $result = $this->jwtService->getTokenFromHeader();
        $this->assertNull($result);
    }
} 