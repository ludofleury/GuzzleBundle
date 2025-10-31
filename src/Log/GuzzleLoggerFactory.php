<?php

namespace Playbloom\Bundle\GuzzleBundle\Log;

use Psr\Log\LoggerInterface;

class GuzzleLoggerFactory
{
    /**
     * @param LoggerInterface $logger Logger instance
     * @param string|null $format Log format
     * @return LoggerPlugin|LoggerMiddleware|LoggerSubscriber
     * @throws \RuntimeException When no compatible Guzzle installation found
     */
    public static function create(LoggerInterface $logger, $format = null)
    {
        if (class_exists('Guzzle\Plugin\Log\LogPlugin')) {
            return new LoggerPlugin($logger, $format);
        }
        
        if (class_exists('GuzzleHttp\Middleware') && class_exists('GuzzleHttp\MessageFormatter')) {
            return new LoggerMiddleware($logger, $format);
        }
        
        if (class_exists('GuzzleHttp\Subscriber\Log\LogSubscriber')) {
            return new LoggerSubscriber($logger, $format);
        }
        
        throw new \RuntimeException('No compatible Guzzle installation found');
    }
}
