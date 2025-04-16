<?php

namespace Playbloom\Bundle\GuzzleBundle\Tests\DataCollector;

use Guzzle\Plugin\History\HistoryPlugin;

/**
 * Fake History plugin
 */
class HistoryPluginStub extends HistoryPlugin implements \IteratorAggregate
{
    private $stubJournal = array();

    public function __construct(array $stubJournal)
    {
        $this->stubJournal = $stubJournal;
    }

    /**
     * Get the requests in the history
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->stubJournal);
    }
}
