<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\Stopwatch\Stopwatch;

class HistoryMiddleware implements \IteratorAggregate, \Countable
{
    private $container = [];
    private $middleware;
    private $limit = 100;
    private $stopwatch;
    private $requestCount = 0;

    /**
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     */
    public function __construct(Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch;
        $this->middleware = \GuzzleHttp\Middleware::history($this->container);
    }

    /**
     * @return callable
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param int $limit Maximum number of requests to store
     * @return void
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_slice($this->container, 0, $this->limit));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return min(count($this->container), $this->limit);
    }

    /**
     * @param callable $handler Handler function
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        $historyHandler = call_user_func($this->middleware, $handler);
        $container = &$this->container;
        $stopwatch = $this->stopwatch;
        $requestCount = &$this->requestCount;

        return function ($request, array $options) use ($historyHandler, &$container, $stopwatch, &$requestCount) {
            $previousStatsCallback = isset($options['on_stats']) ? $options['on_stats'] : null;
            $transferStats = null;
            
            $options['on_stats'] = function (\GuzzleHttp\TransferStats $stats) use (&$transferStats, $previousStatsCallback) {
                $transferStats = $stats;
                
                if ($previousStatsCallback) {
                    $previousStatsCallback($stats);
                }
            };

            if ($stopwatch) {
                $requestCount++;
                $method = $request->getMethod();
                $uri = $request->getUri();
                $eventName = sprintf(
                    'guzzle.%d [%s %s%s]',
                    $requestCount,
                    $method,
                    $uri->getHost(),
                    $uri->getPath()
                );

                $stopwatch->start($eventName, 'guzzle');

                return $historyHandler($request, $options)->then(
                    function ($response) use ($eventName, $stopwatch, &$container, &$transferStats) {
                        $stopwatch->stop($eventName);
                        if ($transferStats !== null) {
                            $lastIndex = count($container) - 1;
                            if ($lastIndex >= 0 && isset($container[$lastIndex])) {
                                $container[$lastIndex]['transfer_stats'] = $transferStats;
                            }
                        }
                        return $response;
                    },
                    function ($reason) use ($eventName, $stopwatch, &$container, &$transferStats) {
                        $stopwatch->stop($eventName);
                        if ($transferStats !== null) {
                            $lastIndex = count($container) - 1;
                            if ($lastIndex >= 0 && isset($container[$lastIndex])) {
                                $container[$lastIndex]['transfer_stats'] = $transferStats;
                            }
                        }
                        throw $reason;
                    }
                );
            }

            return $historyHandler($request, $options);
        };
    }
}
