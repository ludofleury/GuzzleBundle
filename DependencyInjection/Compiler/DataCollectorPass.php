<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\ProfilerPass;

class DataCollectorPass extends ProfilerPass
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('profiler')) {
            return;
        }

        $taggedCollectors = $container->findTaggedServiceIds('data_collector.guzzle');

        $replaced = false;

        if (count($taggedCollectors) > 0) {

            foreach ($taggedCollectors as $id => $tagAttributes) {
                $attributes = $tagAttributes[0];

                if (isset($attributes['replace']) && $attributes['replace'] === true) {
                    $replaced = true;
                }

                $container->findDefinition($id)->addTag('data_collector', $attributes);
            }
        }

        if (!$replaced) {
            $container->findDefinition('data_collector.guzzle')->addTag('data_collector', array('template' => 'PlaybloomGuzzleBundle:Collector:guzzle', 'id' => 'guzzle'));
        }

        $definition = $container->getDefinition('profiler');
        $definition->setMethodCalls();
        parent::process($container);
    }
}
