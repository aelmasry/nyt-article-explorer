<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use App\Utils\Logger;
use PDO;
use PDOStatement;

class AuthServiceTest extends TestCase
{
    private $authService;
    private $mockPdo;
    private $mockStatement;
    private $mockLogger;
    private $testJwtSecret = 'test_secret_key';
    private $testJwtExpiry = 3600;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock PDO
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        
        // Mock Logger
        $this->mockLogger = $this->createMock(Logger::class);
        
        // Set up environment variables
        $_ENV['JWT_SECRET'] = $this->testJwtSecret;
        $_ENV['JWT_EXPIRY'] = $this->testJwtExpiry;
        
        $this->authService = new AuthService();
    }

    public function testLoginSuccess()
    {
        $username = 'testuser';
        $password = 'testpass';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $user = [
            'id' => 1,
            'username' => $username,
            'email' => 'test@example.com',
            'password' => $hashedPassword
        ];
        
        $this->mockStatement->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [[':username' => $username]],
                [$this->matchesRegularExpression('/^.*$/')]
            );
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($user);
            
        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->authService->login($username, $password);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user['id'], $result['user']['id']);
        $this->assertEquals($user['username'], $result['user']['username']);
        $this->assertEquals($user['email'], $result['user']['email']);
        $this->assertIsString($result['token']);
    }

    public function testLoginFailure()
    {
        $username = 'testuser';
        $password = 'wrongpass';
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([':username' => $username]);
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->authService->login($username, $password);
        
        $this->assertNull($result);
    }

    public function testRegisterSuccess()
    {
        $username = 'newuser';
        $email = 'new@example.com';
        $password = 'newpass';
        
        $this->mockStatement->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    [
                        ':username' => $username,
                        ':email' => $email
                    ]
                ],
                [
                    [
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $this->matchesRegularExpression('/^.*$/')
                    ]
                ]
            );
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
            
        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $this->mockPdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(1);
            
        $result = $this->authService->register($username, $email, $password);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals(1, $result['user']['id']);
        $this->assertEquals($username, $result['user']['username']);
        $this->assertEquals($email, $result['user']['email']);
        $this->assertIsString($result['token']);
    }

    public function testRegisterFailure()
    {
        $username = 'existinguser';
        $email = 'existing@example.com';
        $password = 'newpass';
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([
                ':username' => $username,
                ':email' => $email
            ]);
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 1]);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->authService->register($username, $email, $password);
        
        $this->assertNull($result);
    }

    public function testValidateToken()
    {
        $token = 'valid.token.here';
        $userId = 1;
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([
                ':user_id' => $userId,
                ':token' => $token
            ]);
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['id' => 1]);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->authService->validateToken($token);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('exp', $result);
        $this->assertEquals($userId, $result['user_id']);
    }

    public function testLogout()
    {
        $token = 'token.to.invalidate';
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([':token' => $token]);
            
        $this->mockStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->authService->logout($token);
        
        $this->assertTrue($result);
    }
} 