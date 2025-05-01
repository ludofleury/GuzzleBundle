<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('playbloom_guzzle');

        $rootNode
            ->children()
                ->booleanNode('web_profiler')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
