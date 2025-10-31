<?php

namespace Playbloom\Bundle\GuzzleBundle\Log;

use Psr\Log\LoggerInterface;

class LoggerPlugin extends \Guzzle\Plugin\Log\LogPlugin
{
    /**
     * @param LoggerInterface $logger Logger instance
     * @param string|null $format Log message template
     */
    public function __construct(LoggerInterface $logger, $format = null)
    {
        $adapter = new \Guzzle\Log\MonologLogAdapter($logger);
        $template = $format ?: 'Requested "{host}" {method} "{resource}"';
        parent::__construct($adapter, $template);
    }
}
