<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\NytApiService;
use PDO;
use PDOStatement;

class NytApiServiceTest extends TestCase
{
    private $nytApiService;
    private $mockPdo;
    private $mockStatement;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock PDO
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        
        // Set up environment variables
        $_ENV['NYT_API_KEY'] = 'test_api_key';
        $_ENV['NYT_API_BASE_URL'] = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';
        $_ENV['CACHE_DURATION'] = '3600';
        
        $this->nytApiService = new NytApiService();
    }

    public function testBuildSearchUrl()
    {
        $query = 'test query';
        $page = 0;
        
        $expectedUrl = 'https://api.nytimes.com/svc/search/v2/articlesearch.json?q=test+query&page=0&api-key=test_api_key&sort=newest';
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->nytApiService);
        $method = $reflection->getMethod('buildSearchUrl');
        $method->setAccessible(true);
        
        $actualUrl = $method->invoke($this->nytApiService, $query, $page);
        
        $this->assertEquals($expectedUrl, $actualUrl);
    }

    public function testExtractArticleId()
    {
        $validUrl = 'https://www.nytimes.com/2024/04/02/world/test-article.html';
        $invalidUrl = 'https://www.nytimes.com/invalid-url';
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->nytApiService);
        $method = $reflection->getMethod('extractArticleId');
        $method->setAccessible(true);
        
        $validId = $method->invoke($this->nytApiService, $validUrl);
        $invalidId = $method->invoke($this->nytApiService, $invalidUrl);
        
        $this->assertEquals('2024/04/02/world/test-article.html', $validId);
        $this->assertNull($invalidId);
    }

    public function testGetFromCache()
    {
        $cacheKey = 'test_key';
        $cachedData = ['test' => 'data'];
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([$cacheKey]);
            
        $this->mockStatement->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(['data' => json_encode($cachedData)]);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->nytApiService);
        $method = $reflection->getMethod('getFromCache');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->nytApiService, $cacheKey);
        
        $this->assertEquals($cachedData, $result);
    }

    public function testSaveToCache()
    {
        $cacheKey = 'test_key';
        $data = ['test' => 'data'];
        
        $this->mockStatement->expects($this->once())
            ->method('execute')
            ->with([
                $cacheKey,
                json_encode($data),
                $this->matchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
            ]);
            
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);
            
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->nytApiService);
        $method = $reflection->getMethod('saveToCache');
        $method->setAccessible(true);
        
        $method->invoke($this->nytApiService, $cacheKey, $data);
        
        // No assertion needed as we're just verifying the mock was called correctly
    }
} 