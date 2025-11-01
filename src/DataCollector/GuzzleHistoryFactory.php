<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\Stopwatch\Stopwatch;

class GuzzleHistoryFactory
{
    /**
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     * @return HistoryMiddleware
     */
    public static function createMiddleware(Stopwatch $stopwatch = null)
    {
        return new HistoryMiddleware($stopwatch);
    }

    /**
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     * @return HistorySubscriber
     */
    public static function createSubscriber(Stopwatch $stopwatch = null)
    {
        return new HistorySubscriber(null, $stopwatch);
    }
}
