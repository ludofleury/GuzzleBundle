<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection;

class GuzzleClientConfigurator
{
    private $plugins;

    /**
     * @param array $plugins Array of plugins to configure
     */
    public function __construct(array $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @param mixed $client Guzzle client instance
     * @return mixed Configured client
     */
    public function configure($client)
    {
        foreach ($this->plugins as $plugin) {
            $this->attachPlugin($client, $plugin);
        }

        return $client;
    }

    /**
     * @param mixed $client Guzzle client instance
     * @param mixed $plugin Plugin to attach
     * @return void
     */
    private function attachPlugin($client, $plugin)
    {
        if (method_exists($client, 'getEmitter')) {
            $client->getEmitter()->attach($plugin);
            return;
        }

        if (method_exists($client, 'getConfig')) {
            $handler = $client->getConfig('handler');
            if ($handler && method_exists($handler, 'push')) {
                $handler->push($plugin);
            }
        }
    }
}
