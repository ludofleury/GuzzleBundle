<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\Stopwatch\Stopwatch;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;

class HistorySubscriber extends \GuzzleHttp\Subscriber\History
{
    private $stopwatch;
    private $requestCount = 0;
    private $activeEvents = [];

    /**
     * @param int|null $limit Maximum number of requests to store
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     */
    public function __construct($limit = null, Stopwatch $stopwatch = null)
    {
        parent::__construct($limit);
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return array_merge(parent::getEvents(), [
            'before' => ['onBefore', 'first'],
            'complete' => ['onComplete', 'last'],
            'error' => ['onError', 'last'],
        ]);
    }

    /**
     * @param BeforeEvent $event Event triggered before request
     * @return void
     */
    public function onBefore(BeforeEvent $event)
    {
        if (!$this->stopwatch) {
            return;
        }

        $request = $event->getRequest();
        $this->requestCount++;
        $eventName = sprintf(
            'guzzle.%d [%s %s]',
            $this->requestCount,
            $request->getMethod(),
            $request->getUrl()
        );

        $this->activeEvents[spl_object_hash($request)] = $eventName;
        $this->stopwatch->start($eventName, 'guzzle');
    }

    /**
     * @param CompleteEvent $event Event triggered on request completion
     * @return void
     */
    public function onComplete(CompleteEvent $event)
    {
        $this->stopRequestEvent($event->getRequest());
    }

    /**
     * @param ErrorEvent $event Event triggered on request error
     * @return void
     */
    public function onError(ErrorEvent $event)
    {
        $this->stopRequestEvent($event->getRequest());
    }

    /**
     * @param \GuzzleHttp\Message\RequestInterface $request Request to stop tracking
     * @return void
     */
    private function stopRequestEvent($request)
    {
        if (!$this->stopwatch) {
            return;
        }

        $hash = spl_object_hash($request);
        if (isset($this->activeEvents[$hash])) {
            $this->stopwatch->stop($this->activeEvents[$hash]);
            unset($this->activeEvents[$hash]);
        }
    }
}
