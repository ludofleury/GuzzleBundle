<?php

namespace Playbloom\Bundle\GuzzleBundle\Tests\DataCollector;

/**
 * Fake History plugin for testing - compatible with modern Guzzle versions
 */
class HistoryPluginStub implements \IteratorAggregate, \Countable
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
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->stubJournal);
    }

    /**
     * Count the number of transactions
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->stubJournal);
    }

    /**
     * Add a transaction to the history
     *
     * @param array $transaction
     */
    public function addTransaction(array $transaction)
    {
        $this->stubJournal[] = $transaction;
    }

    /**
     * Clear the history
     */
    public function clear()
    {
        $this->stubJournal = array();
    }
}
