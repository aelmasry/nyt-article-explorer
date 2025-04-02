<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RateLimitService;
use PDO;
use PDOStatement;

class RateLimitServiceTest extends TestCase
{
    private $rateLimitService;
    private $mockPdo;
    private $mockStatement;
    private $testMaxRequests = 100;
    private $testWindowMinutes = 60;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock PDO
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        
        // Set up environment variables
        $_ENV['RATE_LIMIT_MAX_REQUESTS'] = $this->testMaxRequests;
        $_ENV['RATE_LIMIT_WINDOW_MINUTES'] = $this->testWindowMinutes;
        
        $this->rateLimitService = new RateLimitService();
    }

    public function testCheckRateLimitNewRecord()
    {
        $ipAddress = '127.0.0.1';
        $token = null;
        
        // Mock empty result for first query
        $this->mockStatement->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->equalTo([$ipAddress, $token, $token])],
                [$this->equalTo([$ipAddress, $token, $this->matchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/), $this->matchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/)])]
            );
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
            
        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->rateLimitService->checkRateLimit($ipAddress, $token);
        
        $this->assertTrue($result['allowed']);
        $this->assertNull($result['retryAfter']);
    }

    public function testCheckRateLimitWithinWindow()
    {
        $ipAddress = '127.0.0.1';
        $token = 'test_token';
        $now = date('Y-m-d H:i:s');
        
        // Mock existing record
        $record = [
            'ip_address' => $ipAddress,
            'token' => $token,
            'request_count' => 50,
            'first_request_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'last_request_at' => $now
        ];
        
        $this->mockStatement->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->equalTo([$ipAddress, $token, $token])],
                [$this->equalTo([$now, $ipAddress, $token, $token])]
            );
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($record);
            
        $this->mockPdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->rateLimitService->checkRateLimit($ipAddress, $token);
        
        $this->assertTrue($result['allowed']);
        $this->assertNull($result['retryAfter']);
    }

    public function testCheckRateLimitExceeded()
    {
        $ipAddress = '127.0.0.1';
        $token = 'test_token';
        $now = date('Y-m-d H:i:s');
        
        // Mock existing record with exceeded limit
        $record = [
            'ip_address' => $ipAddress,
            'token' => $token,
            'request_count' => $this->testMaxRequests,
            'first_request_at' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'last_request_at' => $now
        ];
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([$ipAddress, $token, $token]);
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($record);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        $result = $this->rateLimitService->checkRateLimit($ipAddress, $token);
        
        $this->assertFalse($result['allowed']);
        $this->assertIsInt($result['retryAfter']);
        $this->assertGreaterThan(0, $result['retryAfter']);
        $this->assertLessThanOrEqual($this->testWindowMinutes * 60, $result['retryAfter']);
    }

    public function testCleanupOldRecords()
    {
        $now = date('Y-m-d H:i:s');
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([$now, $this->testWindowMinutes]);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->rateLimitService);
        $method = $reflection->getMethod('cleanupOldRecords');
        $method->setAccessible(true);
        
        $method->invoke($this->rateLimitService, $now);
        
        // No assertion needed as we're just verifying the mock was called correctly
    }
} 