<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Guzzle\Plugin\History\HistoryPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * GuzzleDataCollector.
 *
 * @author Ludovic Fleury <ludo.flery@gmail.com>
 */
class GuzzleDataCollector extends DataCollector
{
    private $profiler;

    public function __construct(HistoryPlugin $profiler)
    {
        $this->profiler = $profiler;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'calls'       => array(),
            'error_count' => 0,
            'methods'     => array(),
            'total_time'  => 0,
        );

        foreach ($this->profiler as $call) {
            $error = false;
            $request = $call;
            $response = $request->getResponse();

            $requestContent = null;
            $responseContent = $response->getBody(true);

            $time = array(
                'total' => $response->getInfo('total_time'),
                'connection' => $response->getInfo('connect_time')
            );

            $this->data['total_time'] += $response->getInfo('total_time');

            if (!isset($this->data['methods'][$request->getMethod()])) {
                $this->data['methods'][$request->getMethod()] = 0;
            }

            $this->data['methods'][$request->getMethod()]++;

            if ($response->isError()) {
                $this->data['error_count']++;
                $error = true;
            }

            $this->data['calls'][] = array(
                'request' => $request,
                'requestContent' => $requestContent,
                'response' => $response,
                'responseContent' => $responseContent,
                'time' => $time,
                'error' => $error
            );
        }
    }

    /**
     * @return array
     */
    public function getCalls()
    {
        return isset($this->data['calls']) ? $this->data['calls'] : array();
    }

    /**
     * @return int
     */
    public function countErrors()
    {
        return isset($this->data['error_count']) ? $this->data['error_count'] : 0;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return isset($this->data['methods']) ? $this->data['methods'] : array();
    }

    /**
     * @return int
     */
    public function getTotalTime()
    {
        return isset($this->data['total_time']) ? $this->data['total_time'] : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'guzzle';
    }
}
