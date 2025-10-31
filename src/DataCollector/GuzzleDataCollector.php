<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * GuzzleDataCollector.
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class GuzzleDataCollector extends DataCollector
{
    private $profiler;

    /**
     * @param \Traversable|\IteratorAggregate $profiler History plugin/subscriber/middleware
     */
    public function __construct($profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     * @param \Exception|null $exception Exception if any
     * @return void
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $data = array(
            'calls'       => array(),
            'error_count' => 0,
            'methods'     => array(),
            'total_time'  => 0,
        );

        /**
         * Aggregates global metrics about Guzzle usage
         *
         * @param array $request
         * @param array $response
         * @param array $time
         * @param bool  $error
         */
        $aggregate = function ($request, $response, $time, $error) use (&$data) {

            $method = $request['method'];
            if (!isset($data['methods'][$method])) {
                $data['methods'][$method] = 0;
            }

            $data['methods'][$method]++;
            $data['total_time'] += $time['total'];
            $data['error_count'] += (int) $error;
        };

        foreach ($this->profiler as $transaction) {
            $request = $this->collectRequest($transaction);
            $response = $this->collectResponse($transaction);
            $time = $this->collectTime($transaction);
            $error = $this->isError($transaction);

            $aggregate($request, $response, $time, $error);

            $data['calls'][] = array(
                'request' => $request,
                'response' => $response,
                'time' => $time,
                'error' => $error
            );
        }

        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return isset($this->data['calls']) ? $this->data['calls'] : array();
    }

    /**
     * @return int
     */
    public function countErrors()
    {
        return isset($this->data['error_count']) ? $this->data['error_count'] : 0;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return isset($this->data['methods']) ? $this->data['methods'] : array();
    }

    /**
     * @return int
     */
    public function getTotalTime()
    {
        return isset($this->data['total_time']) ? $this->data['total_time'] : 0;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'guzzle';
    }

    /**
     * @param mixed $transaction Transaction data
     * @return array
     * @throws \InvalidArgumentException When request type is unsupported
     */
    private function collectRequest($transaction)
    {
        $request = $this->getRequest($transaction);

        if (interface_exists('Guzzle\Http\Message\RequestInterface') &&
            $request instanceof \Guzzle\Http\Message\RequestInterface) {
            $body = null;
            if ($request instanceof \Guzzle\Http\Message\EntityEnclosingRequestInterface) {
                $body = (string) $request->getBody();
            }

            return [
                'headers' => $request->getHeaders(),
                'method'  => $request->getMethod(),
                'scheme'  => $request->getScheme(),
                'host'    => $request->getHost(),
                'port'    => $request->getPort(),
                'path'    => $request->getPath(),
                'query'   => $request->getQuery(),
                'body'    => $body
            ];
        }

        if (interface_exists('Psr\Http\Message\RequestInterface') &&
            $request instanceof \Psr\Http\Message\RequestInterface) {
            $uri = $request->getUri();
            $queryString = $uri->getQuery();
            $queryArray = [];
            if ($queryString) {
                parse_str($queryString, $queryArray);
            }
            return [
                'headers' => $request->getHeaders(),
                'method'  => $request->getMethod(),
                'scheme'  => $uri->getScheme(),
                'host'    => $uri->getHost(),
                'port'    => $uri->getPort(),
                'path'    => $uri->getPath(),
                'query'   => $queryArray ?: $queryString,
                'body'    => (string) $request->getBody()
            ];
        }

        if (interface_exists('GuzzleHttp\Message\RequestInterface') &&
            $request instanceof \GuzzleHttp\Message\RequestInterface) {
            $uri = parse_url($request->getUrl());
            return [
                'headers' => $request->getHeaders(),
                'method'  => $request->getMethod(),
                'scheme'  => $uri['scheme'] ?? 'http',
                'host'    => $uri['host'] ?? '',
                'port'    => $uri['port'] ?? null,
                'path'    => $uri['path'] ?? '/',
                'query'   => $uri['query'] ?? '',
                'body'    => method_exists($request, 'getBody') ? (string) $request->getBody() : null
            ];
        }

        throw new \InvalidArgumentException('Unsupported request type');
    }

    /**
     * @param mixed $transaction Transaction data
     * @return array
     */
    private function collectResponse($transaction)
    {
        $response = $this->getResponse($transaction);
        if (!$response) {
            return [
                'statusCode'   => 0,
                'reasonPhrase' => 'No response',
                'headers'      => [],
                'body'         => ''
            ];
        }

        if (interface_exists('Guzzle\Http\Message\Response') &&
            $response instanceof \Guzzle\Http\Message\Response) {
            return [
                'statusCode'   => $response->getStatusCode(),
                'reasonPhrase' => $response->getReasonPhrase(),
                'headers'      => $response->getHeaders(),
                'body'         => $response->getBody(true)
            ];
        }

        return [
            'statusCode'   => $response->getStatusCode(),
            'reasonPhrase' => $response->getReasonPhrase(),
            'headers'      => $response->getHeaders(),
            'body'         => (string) $response->getBody()
        ];
    }

    /**
     * @param mixed $transaction Transaction data
     * @return array
     */
    private function collectTime($transaction)
    {
        $totalTime = 0;
        $connectTime = 0;

        if (is_array($transaction) && isset($transaction['transfer_stats'])) {
            $stats = $transaction['transfer_stats'];
            if ($stats instanceof \GuzzleHttp\TransferStats) {
                $totalTime = $stats->getTransferTime();
                $handlerStats = $stats->getHandlerStats();
                
                if (isset($handlerStats['connect_time'])) {
                    $connectTime = $handlerStats['connect_time'];
                } elseif (isset($handlerStats['pretransfer_time'])) {
                    $connectTime = $handlerStats['pretransfer_time'];
                } elseif (isset($handlerStats['namelookup_time'])) {
                    $connectTime = $handlerStats['namelookup_time'];
                }
            }
        } else {
            $response = $this->getResponse($transaction);
            if ($response && method_exists($response, 'getInfo')) {
                $totalTime = $response->getInfo('total_time');
                $connectTime = $response->getInfo('connect_time');
            }
        }

        return [
            'total'      => $totalTime ?: 0,
            'connection' => $connectTime ?: 0
        ];
    }

    /**
     * @param mixed $transaction Transaction data
     * @return bool
     */
    private function isError($transaction)
    {
        $response = $this->getResponse($transaction);
        if (!$response) {
            return true;
        }

        if (method_exists($response, 'isError')) {
            return $response->isError();
        }

        return $response->getStatusCode() >= 400;
    }

    /**
     * @param mixed $transaction Transaction data
     * @return mixed
     */
    private function getRequest($transaction)
    {
        if (is_array($transaction) && isset($transaction['request'])) {
            return $transaction['request'];
        }

        if (is_object($transaction) && method_exists($transaction, 'getRequest')) {
            return $transaction->getRequest();
        }

        return $transaction;
    }

    /**
     * @param mixed $transaction Transaction data
     * @return mixed|null
     */
    private function getResponse($transaction)
    {
        if (is_array($transaction) && isset($transaction['response'])) {
            return $transaction['response'];
        }

        if (is_object($transaction) && method_exists($transaction, 'getResponse')) {
            return $transaction->getResponse();
        }

        if (isset($transaction['error'])) {
            return null;
        }

        return null;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->data = [];
    }
}
