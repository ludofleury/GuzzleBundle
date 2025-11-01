<?php

namespace Playbloom\Bundle\GuzzleBundle\Tests\DataCollector;

use Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Guzzle DataCollector unit test
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class GuzzleDataCollectorTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists('Psr\Http\Message\RequestInterface')) {
            $this->markTestSkipped('PSR-7 interfaces not available (Guzzle 4/5 use their own interfaces)');
        }
    }

    public function testGetName()
    {
        $guzzleDataCollector = $this->createGuzzleCollector();

        $this->assertEquals($guzzleDataCollector->getName(), 'guzzle');
    }

    /**
     * Test an empty GuzzleDataCollector
     */
    public function testCollectEmpty()
    {
        // test an empty collector
        /** @var GuzzleDataCollector $guzzleDataCollector */
        $guzzleDataCollector = $this->createGuzzleCollector();

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->getMock();
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->getMock();
        /** @var GuzzleDataCollector $guzzleDataCollector */
        $guzzleDataCollector->collect($request, $response);

        $this->assertEquals($guzzleDataCollector->getCalls(), array());
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), array());
        $this->assertEquals($guzzleDataCollector->getTotalTime(), 0);
    }

    /**
     * Test a DataCollector containing one valid call
     *
     * HTTP response code 100+ and 200+
     */
    public function testCollectValidCall()
    {
        // test a regular call
        $callInfos = array('connect_time' => 15, 'total_time' => 150);
        $callUrlQuery = $this->stubQuery(array('foo' => 'bar'));
        $callRequest = $this->stubRequest('get', 'http', 'test.local', '/', $callUrlQuery);
        $callResponse = $this->stubResponse(200, 'OK', 'Hello world');
        $call = $this->stubCall($callRequest, $callResponse, $callInfos);
        /** @var GuzzleDataCollector $guzzleDataCollector */
        $guzzleDataCollector = $this->createGuzzleCollector(array($call));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $guzzleDataCollector->collect($request, $response);

        $this->assertCount(1, $guzzleDataCollector->getCalls());
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), array('get' => 1));
        $this->assertEquals(0.15, $guzzleDataCollector->getTotalTime());

        $calls = $guzzleDataCollector->getCalls();
        $this->assertEquals(
            $calls[0],
            array(
                'request' => array(
                    'headers' => [],
                    'method'  => 'get',
                    'scheme'  => 'http',
                    'host'    => 'test.local',
                    'port'    => 80,
                    'path'    => '/',
                    'query'   => ['foo' => 'bar'],
                    'body'    => ''
                ),
                'response' => array(
                    'statusCode'   => 200,
                    'reasonPhrase' => 'OK',
                    'headers'      => [],
                    'body'         => 'Hello world',
                ),
                'time' => array(
                    'total'      => 0.15,
                    'connection' => 0.015
                ),
                'error' => false
            )
        );
    }

    /**
     * Test a DataCollector containing one faulty call
     *
     * HTTP response code 400+ & 500+
     */
    public function testCollectErrorCall()
    {
        // test an error call
        $callInfos = array('connect_time' => 15, 'total_time' => 150);
        $callUrlQuery = $this->stubQuery(array('foo' => 'bar'));
        $callRequest = $this->stubRequest('post', 'http', 'test.local', '/', $callUrlQuery);
        $callResponse = $this->stubResponse(404, 'Not found', 'Oops');
        $call = $this->stubCall($callRequest, $callResponse, $callInfos);
        /** @var GuzzleDataCollector $guzzleDataCollector */
        $guzzleDataCollector = $this->createGuzzleCollector(array($call));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->getMock();
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->getMock();
        $guzzleDataCollector->collect($request, $response);

        $this->assertCount(1, $guzzleDataCollector->getCalls());
        $this->assertEquals($guzzleDataCollector->countErrors(), 1);
        $this->assertEquals($guzzleDataCollector->getMethods(), array('post' => 1));
        $this->assertEquals(0.15, $guzzleDataCollector->getTotalTime());

        $calls = $guzzleDataCollector->getCalls();
        $this->assertEquals(
            $calls[0],
            array(
                'request' => array(
                    'headers' => [],
                    'method'  => 'post',
                    'scheme'  => 'http',
                    'host'    => 'test.local',
                    'port'    => 80,
                    'path'    => '/',
                    'query'   => ['foo' => 'bar'],
                    'body'    => '',
                ),
                'response' => array(
                    'statusCode'   => 404,
                    'reasonPhrase' => 'Not found',
                    'headers'      => [],
                    'body'         => 'Oops',
                ),
                'time' => array(
                    'total'      => 0.15,
                    'connection' => 0.015
                ),
                'error' => true
            )
        );
    }

    /**
     * Test a DataCollector containing one call with request content
     *
     * The request has a body content like POST or PUT
     * In this case the call contains a Guzzle\Http\Message\EntityEnclosingRequestInterface
     * which should be sanitized/casted as a string
     */
    public function testCollectBodyRequestCall()
    {
        $callInfos = array('connect_time' => 15, 'total_time' => 150);
        $callUrlQuery = $this->stubQuery(array('foo' => 'bar'));
        $callRequest = $this->stubRequest('post', 'http', 'test.local', '/', $callUrlQuery, 'Request body string');
        $callResponse = $this->stubResponse(201, 'Created', '');
        $call = $this->stubCall($callRequest, $callResponse, $callInfos);
        $guzzleDataCollector = $this->createGuzzleCollector(array($call));

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->getMock();
        $response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->getMock();
        $guzzleDataCollector->collect($request, $response);

        $this->assertEquals(count($guzzleDataCollector->getCalls()), 1);
        $this->assertEquals($guzzleDataCollector->countErrors(), 0);
        $this->assertEquals($guzzleDataCollector->getMethods(), array('post' => 1));
        $this->assertEquals(0.15, $guzzleDataCollector->getTotalTime());

        $calls = $guzzleDataCollector->getCalls();
        $this->assertEquals(
            $calls[0],
            array(
                'request' => array(
                    'headers' => [],
                    'method'  => 'post',
                    'scheme'  => 'http',
                    'host'    => 'test.local',
                    'port'    => 80,
                    'path'    => '/',
                    'query'   => ['foo' => 'bar'],
                    'body'    => 'Request body string',
                ),
                'response' => array(
                    'statusCode'   => 201,
                    'reasonPhrase' => 'Created',
                    'headers'      => [],
                    'body'         => '',
                ),
                'time' => array(
                    'total'      => 0.15,
                    'connection' => 0.015
                ),
                'error' => false
            )
        );
    }

    protected function createGuzzleCollector(array $calls = array())
    {
        return new GuzzleDataCollector(new HistoryPluginStub($calls));
    }

    protected function stubCall($request, $response, array $info)
    {
        return [
            'request' => $request,
            'response' => $response,
            'transfer_stats' => $this->createTransferStats($info)
        ];
    }

    protected function createTransferStats(array $info)
    {
        // Create a mock request for TransferStats constructor
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);

        // Create real TransferStats instance
        return new \GuzzleHttp\TransferStats(
            $request,
            $response,
            $info['total_time'] / 1000, // transferTime in seconds
            null, // handlerErrorData
            [
                'connect_time' => $info['connect_time'] / 1000
            ] // handlerStats
        );
    }

    protected function stubQuery(array $query)
    {
        return http_build_query($query);
    }

    protected function stubRequest($method, $scheme, $host, $path, $query, $body = null)
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getScheme')->willReturn($scheme);
        $uri->method('getHost')->willReturn($host);
        $uri->method('getPort')->willReturn(80);
        $uri->method('getPath')->willReturn($path);
        $uri->method('getQuery')->willReturn($query);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn([]);

        if ($body !== null) {
            $bodyStream = $this->createMock(StreamInterface::class);
            $bodyStream->method('__toString')->willReturn($body);
            $request->method('getBody')->willReturn($bodyStream);
        } else {
            $bodyStream = $this->createMock(StreamInterface::class);
            $bodyStream->method('__toString')->willReturn('');
            $request->method('getBody')->willReturn($bodyStream);
        }

        return $request;
    }

    protected function stubResponse($code, $reason, $body)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($code);
        $response->method('getReasonPhrase')->willReturn($reason);
        $response->method('getHeaders')->willReturn([]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($body);
        $response->method('getBody')->willReturn($bodyStream);

        return $response;
    }

    public function testCollectWithEmptyProfiler()
    {
        $profiler = new HistoryPluginStub([]);
        $collector = new GuzzleDataCollector($profiler);

        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $response = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($request, $response);

        $this->assertEquals([], $collector->getCalls());
        $this->assertEquals(0, $collector->countErrors());
        $this->assertEquals([], $collector->getMethods());
        $this->assertEquals(0, $collector->getTotalTime());
    }

    public function testCollectWithNullResponse()
    {
        $request = $this->createMockRequest('GET', 'https', 'example.com', '/api');

        $transaction = [
            'request' => $request,
            'response' => null,
            'transfer_stats' => $this->createTransferStats(['total_time' => 100, 'connect_time' => 10])
        ];

        $profiler = new HistoryPluginStub([$transaction]);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($httpRequest, $httpResponse);

        $calls = $collector->getCalls();
        $this->assertCount(1, $calls);
        $this->assertEquals(0, $calls[0]['response']['statusCode']);
        $this->assertEquals('No response', $calls[0]['response']['reasonPhrase']);
        $this->assertTrue($calls[0]['error']); // No response is considered an error
    }

    public function testCollectWithDifferentHttpMethods()
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        $transactions = [];

        foreach ($methods as $method) {
            $request = $this->createMockRequest($method, 'https', 'api.example.com', '/test');
            $response = $this->createMockResponse(200, 'OK', 'Success');

            $transactions[] = [
                'request' => $request,
                'response' => $response,
                'transfer_stats' => $this->createTransferStats(['total_time' => 50, 'connect_time' => 5])
            ];
        }

        $profiler = new HistoryPluginStub($transactions);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($httpRequest, $httpResponse);

        $this->assertCount(7, $collector->getCalls());
        $this->assertEquals(0, $collector->countErrors());

        $expectedMethods = array_fill_keys($methods, 1);
        $this->assertEquals($expectedMethods, $collector->getMethods());
    }

    public function testCollectWithVariousStatusCodes()
    {
        $statusCodes = [
            200 => false, // OK - not an error
            201 => false, // Created - not an error
            301 => false, // Moved Permanently - not an error
            400 => true,  // Bad Request - error
            401 => true,  // Unauthorized - error
            404 => true,  // Not Found - error
            500 => true,  // Internal Server Error - error
            503 => true   // Service Unavailable - error
        ];

        $transactions = [];
        foreach ($statusCodes as $code => $isError) {
            $request = $this->createMockRequest('GET', 'https', 'example.com', '/status/' . $code);
            $response = $this->createMockResponse($code, 'Status ' . $code, 'Response body');

            $transactions[] = [
                'request' => $request,
                'response' => $response,
                'transfer_stats' => $this->createTransferStats(['total_time' => 100, 'connect_time' => 10])
            ];
        }

        $profiler = new HistoryPluginStub($transactions);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($httpRequest, $httpResponse);

        $calls = $collector->getCalls();
        $this->assertCount(8, $calls);

        // Count expected errors (status codes >= 400)
        $expectedErrors = count(array_filter($statusCodes));
        $this->assertEquals($expectedErrors, $collector->countErrors());

        // Verify each call has correct error status
        foreach ($calls as $i => $call) {
            $statusCode = $call['response']['statusCode'];
            $expectedError = $statusCodes[$statusCode];
            $this->assertEquals($expectedError, $call['error'], "Status code $statusCode error flag mismatch");
        }
    }

    public function testCollectWithComplexQueryParameters()
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getScheme')->willReturn('https');
        $uri->method('getHost')->willReturn('api.example.com');
        $uri->method('getPort')->willReturn(443);
        $uri->method('getPath')->willReturn('/search');
        $uri->method('getQuery')->willReturn('q=test&limit=10&sort=date&filters[category]=tech&filters[status]=active');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn([
            'Accept' => ['application/json'],
            'User-Agent' => ['GuzzleBundle/1.0']
        ]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn('');
        $request->method('getBody')->willReturn($bodyStream);

        $response = $this->createMockResponse(200, 'OK', '{"results": []}');

        $transaction = [
            'request' => $request,
            'response' => $response,
            'transfer_stats' => $this->createTransferStats(['total_time' => 250, 'connect_time' => 25])
        ];

        $profiler = new HistoryPluginStub([$transaction]);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($httpRequest, $httpResponse);

        $calls = $collector->getCalls();
        $this->assertCount(1, $calls);

        $call = $calls[0];
        $this->assertEquals('GET', $call['request']['method']);
        $this->assertEquals('https', $call['request']['scheme']);
        $this->assertEquals('api.example.com', $call['request']['host']);
        $this->assertEquals(443, $call['request']['port']);
        $this->assertEquals('/search', $call['request']['path']);

        // Query should be parsed into array
        $expectedQuery = [
            'q' => 'test',
            'limit' => '10',
            'sort' => 'date',
            'filters' => [
                'category' => 'tech',
                'status' => 'active'
            ]
        ];
        $this->assertEquals($expectedQuery, $call['request']['query']);
    }

    public function testCollectWithLargeRequestBody()
    {
        $largeBody = str_repeat('This is a large request body. ', 1000); // ~30KB

        $request = $this->createMockRequest('POST', 'https', 'api.example.com', '/upload', $largeBody);
        $response = $this->createMockResponse(201, 'Created', '{"id": 12345}');

        $transaction = [
            'request' => $request,
            'response' => $response,
            'transfer_stats' => $this->createTransferStats(['total_time' => 5000, 'connect_time' => 100])
        ];

        $profiler = new HistoryPluginStub([$transaction]);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        $collector->collect($httpRequest, $httpResponse);

        $calls = $collector->getCalls();
        $this->assertCount(1, $calls);

        $call = $calls[0];
        $this->assertEquals($largeBody, $call['request']['body']);
        $this->assertEquals(5.0, $call['time']['total']); // 5000ms = 5s
        $this->assertEquals(0.1, $call['time']['connection']); // 100ms = 0.1s
    }

    public function testResetMethod()
    {
        $request = $this->createMockRequest('GET', 'https', 'example.com', '/test');
        $response = $this->createMockResponse(200, 'OK', 'Test');

        $transaction = [
            'request' => $request,
            'response' => $response,
            'transfer_stats' => $this->createTransferStats(['total_time' => 100, 'connect_time' => 10])
        ];

        $profiler = new HistoryPluginStub([$transaction]);
        $collector = new GuzzleDataCollector($profiler);

        $httpRequest = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $httpResponse = $this->createMock('Symfony\Component\HttpFoundation\Response');

        // Collect data
        $collector->collect($httpRequest, $httpResponse);

        // Verify data is collected
        $this->assertCount(1, $collector->getCalls());
        $this->assertEquals(1, $collector->countErrors() + count($collector->getCalls()) - $collector->countErrors());

        // Reset collector
        $collector->reset();

        // Verify data is cleared
        $this->assertEquals([], $collector->getCalls());
        $this->assertEquals(0, $collector->countErrors());
        $this->assertEquals([], $collector->getMethods());
        $this->assertEquals(0, $collector->getTotalTime());
    }

    private function createMockRequest($method, $scheme, $host, $path, $body = null)
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getScheme')->willReturn($scheme);
        $uri->method('getHost')->willReturn($host);
        $uri->method('getPort')->willReturn($scheme === 'https' ? 443 : 80);
        $uri->method('getPath')->willReturn($path);
        $uri->method('getQuery')->willReturn('');

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);
        $request->method('getHeaders')->willReturn([]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($body ?: '');
        $request->method('getBody')->willReturn($bodyStream);

        return $request;
    }

    private function createMockResponse($statusCode, $reasonPhrase, $body)
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getReasonPhrase')->willReturn($reasonPhrase);
        $response->method('getHeaders')->willReturn([]);

        $bodyStream = $this->createMock(StreamInterface::class);
        $bodyStream->method('__toString')->willReturn($body);
        $response->method('getBody')->willReturn($bodyStream);

        return $response;
    }
}
