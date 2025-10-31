<?php

namespace Playbloom\Bundle\GuzzleBundle\Log;

use Psr\Log\LoggerInterface;

class LoggerSubscriber extends \GuzzleHttp\Subscriber\Log\LogSubscriber
{
    /**
     * @param LoggerInterface $logger Logger instance
     * @param \GuzzleHttp\Subscriber\Log\Formatter|null $format Message formatter
     */
    public function __construct(LoggerInterface $logger, $format = null)
    {
        $formatter = $format ?: new \GuzzleHttp\Subscriber\Log\Formatter();
        parent::__construct($logger, $formatter);
    }
}
