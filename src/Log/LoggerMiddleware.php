<?php

namespace Playbloom\Bundle\GuzzleBundle\Log;

use Psr\Log\LoggerInterface;

class LoggerMiddleware
{
    private $middleware;

    /**
     * @param LoggerInterface $logger Logger instance
     * @param string|null $format Message format
     */
    public function __construct(LoggerInterface $logger, $format = null)
    {
        $formatter = new \GuzzleHttp\MessageFormatter($format ?: \GuzzleHttp\MessageFormatter::CLF);
        $this->middleware = \GuzzleHttp\Middleware::log($logger, $formatter);
    }

    /**
     * @return callable
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param callable $handler Handler function
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return call_user_func($this->middleware, $handler);
    }
}
