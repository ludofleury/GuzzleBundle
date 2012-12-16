<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

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
            foreach ($plugins as $pluginId => $attributes) {
                $container->findDefinition($clientId)->addMethodCall(
                    'addSubscriber',
                    array(new Reference($pluginId))
                );
            }
        }
    }
}
