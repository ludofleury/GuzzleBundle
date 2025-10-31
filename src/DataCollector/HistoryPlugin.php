<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\Stopwatch\Stopwatch;
use Guzzle\Common\Event;

class HistoryPlugin extends \Guzzle\Plugin\History\HistoryPlugin
{
    private $stopwatch;
    private $requestCount = 0;
    private $activeEvents = [];

    /**
     * @param int $limit Maximum number of requests to store
     * @param Stopwatch|null $stopwatch Stopwatch for profiling
     */
    public function __construct($limit = 10, Stopwatch $stopwatch = null)
    {
        parent::__construct($limit);
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array_merge(parent::getSubscribedEvents(), [
            'request.before_send' => ['onRequestBeforeSend', 9999],
            'request.complete' => ['onRequestComplete', -9999],
            'request.error' => ['onRequestError', -9999],
            'request.exception' => ['onRequestException', -9999],
        ]);
    }

    /**
     * @param Event $event Event triggered before sending request
     * @return void
     */
    public function onRequestBeforeSend(Event $event)
    {
        if (!$this->stopwatch) {
            return;
        }

        $request = $event['request'];
        $this->requestCount++;
        $eventName = sprintf(
            'guzzle.%d [%s %s%s]',
            $this->requestCount,
            $request->getMethod(),
            $request->getHost(),
            $request->getPath()
        );

        $this->activeEvents[spl_object_hash($request)] = $eventName;
        $this->stopwatch->start($eventName, 'guzzle');
    }

    /**
     * @param Event $event Event triggered on request completion
     * @return void
     */
    public function onRequestComplete(Event $event)
    {
        $this->stopRequestEvent($event['request']);
    }

    /**
     * @param Event $event Event triggered on request error
     * @return void
     */
    public function onRequestError(Event $event)
    {
        $this->stopRequestEvent($event['request']);
    }

    /**
     * @param Event $event Event triggered on request exception
     * @return void
     */
    public function onRequestException(Event $event)
    {
        $this->stopRequestEvent($event['request']);
    }

    /**
     * @param \Guzzle\Http\Message\RequestInterface $request Request to stop tracking
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
