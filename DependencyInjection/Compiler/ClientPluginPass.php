<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Client plugin pass
 *
 * Pass responsible to add plugin to every tagged guzzle client
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class ClientPluginPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $clients = $container->findTaggedServiceIds('playbloom_guzzle.client');

        if (empty($clients)) {
            return;
        }

        $plugins = $container->findTaggedServiceIds('playbloom_guzzle.client.plugin');

        foreach ($clients as $clientId => $attribute) {
            $clientDefinition = $container->findDefinition($clientId);

            $this->registerGuzzlePlugin($clientDefinition, $plugins);

            if ($container->hasDefinition('profiler')) {
                $clientDefinition->addMethodCall(
                    'addSubscriber',
                    array(new Reference('playbloom_guzzle.client.plugin.profiler'))
                );
            }
        }
    }

    private function registerGuzzlePlugin($clientDefinition, array $plugins)
    {
        foreach ($plugins as $pluginId => $attributes) {
            $clientDefinition->addMethodCall(
                'addSubscriber',
                array(new Reference($pluginId))
            );
        }
    }
}
