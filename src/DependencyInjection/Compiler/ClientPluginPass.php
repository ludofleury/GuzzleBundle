<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
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
    /**
     * @param ContainerBuilder $container Container builder
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $clients = $container->findTaggedServiceIds('playbloom_guzzle.client');

        if (empty($clients)) {
            return;
        }

        $plugins = $container->findTaggedServiceIds('playbloom_guzzle.client.plugin');

        foreach ($clients as $clientId => $attribute) {
            $clientDefinition = $container->findDefinition($clientId);

            // Collect all plugin references
            $pluginRefs = [];
            foreach ($plugins as $pluginId => $pluginAttributes) {
                $pluginRefs[] = new Reference($pluginId);
            }

            // Add profiler if web profiler is enabled
            if ($container->hasDefinition('profiler')) {
                $pluginRefs[] = new Reference('playbloom_guzzle.client.plugin.profiler');
            }

            // Use a configurator to attach plugins at runtime
            if (!empty($pluginRefs)) {
                $configuratorDef = new Definition('Playbloom\Bundle\GuzzleBundle\DependencyInjection\GuzzleClientConfigurator');
                $configuratorDef->setArguments([$pluginRefs]);
                $clientDefinition->setConfigurator([$configuratorDef, 'configure']);
            }
        }
    }
}
