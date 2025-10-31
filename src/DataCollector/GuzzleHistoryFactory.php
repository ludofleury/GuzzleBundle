<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\Stopwatch\Stopwatch;

class GuzzleHistoryFactory
{
    /**
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     * @return HistoryPlugin|HistoryMiddleware|HistorySubscriber
     * @throws \RuntimeException When no compatible Guzzle installation found
     */
    public static function create(Stopwatch $stopwatch = null)
    {
        if (class_exists('Guzzle\Plugin\History\HistoryPlugin')) {
            return new HistoryPlugin(100, $stopwatch);
        }

        if (class_exists('GuzzleHttp\Middleware')) {
            return new HistoryMiddleware($stopwatch);
        }

        if (class_exists('GuzzleHttp\Subscriber\History')) {
            return new HistorySubscriber(null, $stopwatch);
        }

        throw new \RuntimeException('No compatible Guzzle installation found');
    }
}
