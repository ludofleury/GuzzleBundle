<?php

namespace Playbloom\Bundle\GuzzleBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Guzzle\Plugin\History\HistoryPlugin;

/**
 * GuzzleDataCollector.
 *
 * @author Ludovic Fleury <ludo.flery@gmail.com>
 */
class GuzzleDataCollector extends DataCollector
{
    private $apiKey;
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
            'requests'    => array(),
            'error_count' => 0,
            'methods'     => array(),
            'total_time'  => 0,
            'api_key'     => $this->apiKey
        );

        foreach($this->profiler as $request) {
            $response = $request->getResponse();

            // Dirty hack to avoid stream stringify bug in twig
            $response->rawBody = $response->getBody(true);

            $this->data['requests'][] = $request;
            $this->data['total_time'] += $response->getInfo('total_time');

            if (!isset($this->data['methods'][$request->getMethod()])) {
                $this->data['methods'][$request->getMethod()] = 0;
            }
            $this->data['methods'][$request->getMethod()]++;

            if ($response->isError()) {
                $this->data['error_count']++;
            }
        }
    }

    public function getApiKey()
    {
        return $this->data['api_key'];
    }

    public function getRequests()
    {
        return $this->data['requests'];
    }

    public function countErrors()
    {
        return $this->data['error_count'];
    }

    public function getMethods()
    {
        return $this->data['methods'];
    }

    public function getTotalTime()
    {
        return $this->data['total_time'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'guzzle';
    }
}
