<?php

namespace Playbloom\Bundle\GuzzleBundle\Tests\DataCollector;

use Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleHistoryFactory;
use Playbloom\Bundle\GuzzleBundle\DataCollector\HistoryMiddleware;
use Playbloom\Bundle\GuzzleBundle\DataCollector\HistorySubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * GuzzleHistoryFactory unit test
 */
class GuzzleHistoryFactoryTest extends TestCase
{
    public function testCreateMiddleware()
    {
        if (!class_exists('GuzzleHttp\Middleware')) {
            $this->markTestSkipped('GuzzleHttp\Middleware not available (Guzzle 4/5 use subscribers, not middleware)');
        }

        $middleware = GuzzleHistoryFactory::createMiddleware();
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
    }

    public function testCreateMiddlewareWithStopwatch()
    {
        if (!class_exists('GuzzleHttp\Middleware')) {
            $this->markTestSkipped('GuzzleHttp\Middleware not available (Guzzle 4/5 use subscribers, not middleware)');
        }

        $stopwatch = new Stopwatch();
        $middleware = GuzzleHistoryFactory::createMiddleware($stopwatch);
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
    }

    public function testCreateSubscriber()
    {
        if (!class_exists('GuzzleHttp\Subscriber\History')) {
            $this->markTestSkipped('GuzzleHttp\Subscriber\History not available (Guzzle 6+ does not have subscribers)');
        }

        $subscriber = GuzzleHistoryFactory::createSubscriber();
        $this->assertInstanceOf(HistorySubscriber::class, $subscriber);
    }

    public function testCreateSubscriberWithStopwatch()
    {
        if (!class_exists('GuzzleHttp\Subscriber\History')) {
            $this->markTestSkipped('GuzzleHttp\Subscriber\History not available (Guzzle 6+ does not have subscribers)');
        }

        $stopwatch = new Stopwatch();
        $subscriber = GuzzleHistoryFactory::createSubscriber($stopwatch);
        $this->assertInstanceOf(HistorySubscriber::class, $subscriber);
    }

    public function testCreateMiddlewareReturnsNewInstance()
    {
        if (!class_exists('GuzzleHttp\Middleware')) {
            $this->markTestSkipped('GuzzleHttp\Middleware not available (Guzzle 4/5 use subscribers, not middleware)');
        }

        $middleware1 = GuzzleHistoryFactory::createMiddleware();
        $middleware2 = GuzzleHistoryFactory::createMiddleware();

        $this->assertNotSame($middleware1, $middleware2);
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware1);
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware2);
    }

    public function testCreateSubscriberReturnsNewInstance()
    {
        if (!class_exists('GuzzleHttp\Subscriber\History')) {
            $this->markTestSkipped('GuzzleHttp\Subscriber\History not available (Guzzle 6+ does not have subscribers)');
        }

        $subscriber1 = GuzzleHistoryFactory::createSubscriber();
        $subscriber2 = GuzzleHistoryFactory::createSubscriber();

        $this->assertNotSame($subscriber1, $subscriber2);
        $this->assertInstanceOf(HistorySubscriber::class, $subscriber1);
        $this->assertInstanceOf(HistorySubscriber::class, $subscriber2);
    }

    public function testCreateMiddlewareWithNullStopwatch()
    {
        if (!class_exists('GuzzleHttp\Middleware')) {
            $this->markTestSkipped('GuzzleHttp\Middleware not available (Guzzle 4/5 use subscribers, not middleware)');
        }

        $middleware = GuzzleHistoryFactory::createMiddleware(null);
        $this->assertInstanceOf(HistoryMiddleware::class, $middleware);
    }

    public function testCreateSubscriberWithNullStopwatch()
    {
        if (!class_exists('GuzzleHttp\Subscriber\History')) {
            $this->markTestSkipped('GuzzleHttp\Subscriber\History not available (Guzzle 6+ does not have subscribers)');
        }

        $subscriber = GuzzleHistoryFactory::createSubscriber(null);
        $this->assertInstanceOf(HistorySubscriber::class, $subscriber);
    }

    public function testFactoryMethodsAreStatic()
    {
        $reflection = new \ReflectionClass(GuzzleHistoryFactory::class);

        $createMiddlewareMethod = $reflection->getMethod('createMiddleware');
        $this->assertTrue($createMiddlewareMethod->isStatic());

        $createSubscriberMethod = $reflection->getMethod('createSubscriber');
        $this->assertTrue($createSubscriberMethod->isStatic());
    }

    public function testFactoryMethodsArePublic()
    {
        $reflection = new \ReflectionClass(GuzzleHistoryFactory::class);

        $createMiddlewareMethod = $reflection->getMethod('createMiddleware');
        $this->assertTrue($createMiddlewareMethod->isPublic());

        $createSubscriberMethod = $reflection->getMethod('createSubscriber');
        $this->assertTrue($createSubscriberMethod->isPublic());
    }
}
