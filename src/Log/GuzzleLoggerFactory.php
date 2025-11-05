<?php

namespace Playbloom\Bundle\GuzzleBundle\Log;

use Psr\Log\LoggerInterface;

class GuzzleLoggerFactory
{
    /**
     * @param LoggerInterface $logger Logger instance
     * @param string|null $format Log format
     * @return LoggerMiddleware
     */
    public static function createMiddleware(LoggerInterface $logger, $format = null)
    {
        return new LoggerMiddleware($logger, $format);
    }

    /**
     * @param LoggerInterface $logger Logger instance
     * @param string|null $format Log format
     * @return LoggerSubscriber
     */
    public static function createSubscriber(LoggerInterface $logger, $format = null)
    {
        return new LoggerSubscriber($logger, $format);
    }
}
