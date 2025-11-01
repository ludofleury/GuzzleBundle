<?php

namespace Playbloom\Bundle\GuzzleBundle\Tests\DataCollector;

use Playbloom\Bundle\GuzzleBundle\DataCollector\HistoryMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\TransferStats;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * HistoryMiddleware unit test
 */
class HistoryMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists('GuzzleHttp\Middleware')) {
            $this->markTestSkipped('GuzzleHttp\Middleware not available (Guzzle 4/5 use subscribers, not middleware)');
        }
    }

    public function testConstructor()
    {
        $middleware = new HistoryMiddleware();
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
        $this->assertInstanceOf(\IteratorAggregate::class, $middleware);
        $this->assertInstanceOf(\Countable::class, $middleware);
    }

    public function testConstructorWithStopwatch()
    {
        $stopwatch = new Stopwatch();
        $middleware = new HistoryMiddleware($stopwatch);
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
    }

    public function testGetMiddleware()
    {
        $middleware = new HistoryMiddleware();
        $middlewareCallable = $middleware->getMiddleware();
        $this->assertIsCallable($middlewareCallable);
    }

    public function testSetLimit()
    {
        $middleware = new HistoryMiddleware();
        $middleware->setLimit(50);

        // We can't directly test the limit, but we can test that it doesn't throw
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
    }

    public function testGetIterator()
    {
        $middleware = new HistoryMiddleware();
        $iterator = $middleware->getIterator();
        $this->assertInstanceOf(\Traversable::class, $iterator);
        $this->assertCount(0, $iterator);
    }

    public function testCount()
    {
        $middleware = new HistoryMiddleware();
        $this->assertEquals(0, $middleware->count());
    }

    public function testInvokeWithoutStopwatch()
    {
        $middleware = new HistoryMiddleware();

        // Create mock request
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPath')->willReturn('/test');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        // Create mock response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        // Create a mock handler that returns a fulfilled promise
        $handler = function ($request, $options) use ($response) {
            return new FulfilledPromise($response);
        };

        // Invoke the middleware
        $middlewareHandler = $middleware($handler);
        $this->assertIsCallable($middlewareHandler);

        // Execute the handler
        $promise = $middlewareHandler($request, []);
        $result = $promise->wait();
        $this->assertSame($response, $result);

        // Check that the transaction was recorded
        $this->assertEquals(1, $middleware->count());

        $transactions = iterator_to_array($middleware->getIterator());
        $this->assertCount(1, $transactions);
        $this->assertArrayHasKey('request', $transactions[0]);
        $this->assertArrayHasKey('response', $transactions[0]);
        $this->assertSame($request, $transactions[0]['request']);
        $this->assertSame($response, $transactions[0]['response']);
    }

    public function testInvokeWithStopwatch()
    {
        $stopwatch = new Stopwatch();
        $middleware = new HistoryMiddleware($stopwatch);

        // Create mock request
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPath')->willReturn('/test');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        // Create mock response
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        // Create a mock handler that returns a fulfilled promise
        $handler = function ($request, $options) use ($response) {
            return new FulfilledPromise($response);
        };

        // Invoke the middleware
        $middlewareHandler = $middleware($handler);
        $this->assertIsCallable($middlewareHandler);

        // Execute the handler
        $promise = $middlewareHandler($request, []);
        $result = $promise->wait();
        $this->assertSame($response, $result);

        // Check that the transaction was recorded
        $this->assertEquals(1, $middleware->count());
    }

    public function testInvokeWithStopwatchAndError()
    {
        $stopwatch = new Stopwatch();
        $middleware = new HistoryMiddleware($stopwatch);

        // Create mock request
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPath')->willReturn('/error');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        // Create a handler that throws an exception
        $exception = new \Exception('Test error');
        $handler = function ($request, $options) use ($exception) {
            return new RejectedPromise($exception);
        };

        // Invoke the middleware
        $middlewareHandler = $middleware($handler);

        // Execute the handler and expect exception
        $promise = $middlewareHandler($request, []);

        try {
            $promise->wait();
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertSame($exception, $e);
        }

        // Check that the transaction was still recorded (with error)
        $this->assertEquals(1, $middleware->count());
    }

    public function testInvokeWithTransferStats()
    {
        $middleware = new HistoryMiddleware();

        // Create mock request and response
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPath')->willReturn('/api');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        // Create a handler that calls the on_stats callback
        $handler = function ($request, $options) use ($response) {
            if (isset($options['on_stats'])) {
                $stats = new TransferStats($request, $response, 0.5);
                $options['on_stats']($stats);
            }
            return new FulfilledPromise($response);
        };

        // Invoke the middleware
        $middlewareHandler = $middleware($handler);

        // Execute the handler
        $promise = $middlewareHandler($request, []);
        $result = $promise->wait();
        $this->assertSame($response, $result);

        // Check that transaction was recorded
        $transactions = iterator_to_array($middleware->getIterator());
        $this->assertCount(1, $transactions);
        $this->assertArrayHasKey('request', $transactions[0]);
        $this->assertArrayHasKey('response', $transactions[0]);
    }

    public function testInvokeWithExistingOnStatsCallback()
    {
        $middleware = new HistoryMiddleware();

        // Create mock request and response
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('example.com');
        $uri->method('getPath')->willReturn('/test');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $response = $this->createMock(ResponseInterface::class);

        $originalCallbackCalled = false;
        $originalCallback = function ($stats) use (&$originalCallbackCalled) {
            $originalCallbackCalled = true;
        };

        // Create a handler that calls the on_stats callback
        $handler = function ($request, $options) use ($response) {
            if (isset($options['on_stats'])) {
                $stats = new TransferStats($request, $response, 0.3);
                $options['on_stats']($stats);
            }
            return new FulfilledPromise($response);
        };

        // Invoke the middleware with existing on_stats callback
        $middlewareHandler = $middleware($handler);

        // Execute the handler with existing callback
        $promise = $middlewareHandler($request, ['on_stats' => $originalCallback]);
        $result = $promise->wait();
        $this->assertSame($response, $result);

        // Verify original callback was called
        $this->assertTrue($originalCallbackCalled);
    }

    public function testLimitFunctionality()
    {
        $middleware = new HistoryMiddleware();
        $middleware->setLimit(2);

        // Create multiple requests
        for ($i = 0; $i < 5; $i++) {
            $uri = $this->createMock(UriInterface::class);
            $uri->method('getHost')->willReturn('example.com');
            $uri->method('getPath')->willReturn('/test' . $i);

            $request = $this->createMock(RequestInterface::class);
            $request->method('getMethod')->willReturn('GET');
            $request->method('getUri')->willReturn($uri);

            $response = $this->createMock(ResponseInterface::class);

            $handler = function ($request, $options) use ($response) {
                return new FulfilledPromise($response);
            };

            $middlewareHandler = $middleware($handler);
            $promise = $middlewareHandler($request, []);
            $promise->wait(); // Wait for the promise to complete
        }

        // Should only keep the first 2 transactions due to limit
        $this->assertEquals(2, $middleware->count());

        $transactions = iterator_to_array($middleware->getIterator());
        $this->assertCount(2, $transactions);
    }
}
