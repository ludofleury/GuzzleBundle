<?php

namespace Playbloom\Bundle\GuzzleBundle\DependencyInjection;

use Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleHistoryFactory;
use Playbloom\Bundle\GuzzleBundle\Log\GuzzleLoggerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 */
class PlaybloomGuzzleExtension extends Extension
{
    /**
     * @param array $configs Configuration array
     * @param ContainerBuilder $container Container builder
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $isGuzzle45 = class_exists('GuzzleHttp\Subscriber\History');

        $this->registerLoggerService($container, $isGuzzle45);
        $this->registerProfilerService($container, $isGuzzle45);

        if ($config['web_profiler']) {
            $this->registerDataCollector($container);
        }
    }

    private function registerLoggerService(ContainerBuilder $container, $isGuzzle45)
    {
        if ($isGuzzle45) {
            $definition = new Definition(
                'Playbloom\Bundle\GuzzleBundle\Log\LoggerSubscriber',
                [new Reference('logger'), 'Requested "{host}" {method} "{resource}"']
            );
            $definition->setFactory([GuzzleLoggerFactory::class, 'createSubscriber']);
            $serviceId = 'playbloom_guzzle.client.plugin.subscriber.logger';
        } else {
            $definition = new Definition(
                'Playbloom\Bundle\GuzzleBundle\Log\LoggerMiddleware',
                [new Reference('logger'), 'Requested "{host}" {method} "{resource}"']
            );
            $definition->setFactory([GuzzleLoggerFactory::class, 'createMiddleware']);
            $serviceId = 'playbloom_guzzle.client.plugin.middleware.logger';
        }

        $definition->setPublic(true);
        $definition->addTag('monolog.logger', ['channel' => 'guzzle']);
        $definition->addTag('playbloom_guzzle.client.plugin');
        $container->setDefinition($serviceId, $definition);
        $container->setAlias('playbloom_guzzle.client.plugin.logger', $serviceId);
    }

    private function registerProfilerService(ContainerBuilder $container, $isGuzzle45)
    {
        if ($isGuzzle45) {
            $definition = new Definition(
                'Playbloom\Bundle\GuzzleBundle\DataCollector\HistorySubscriber',
                [new Reference('debug.stopwatch', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE)]
            );
            $definition->setFactory([GuzzleHistoryFactory::class, 'createSubscriber']);
            $serviceId = 'playbloom_guzzle.client.plugin.subscriber.profiler';
        } else {
            $definition = new Definition(
                'Playbloom\Bundle\GuzzleBundle\DataCollector\HistoryMiddleware',
                [new Reference('debug.stopwatch', ContainerBuilder::IGNORE_ON_INVALID_REFERENCE)]
            );
            $definition->setFactory([GuzzleHistoryFactory::class, 'createMiddleware']);
            $serviceId = 'playbloom_guzzle.client.plugin.middleware.profiler';
        }

        $definition->setPublic(true);
        $container->setDefinition($serviceId, $definition);
        $container->setAlias('playbloom_guzzle.client.plugin.profiler', $serviceId);
    }

    private function registerDataCollector(ContainerBuilder $container)
    {
        $definition = new Definition(
            'Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector',
            [new Reference('playbloom_guzzle.client.plugin.profiler')]
        );
        $definition->addTag('data_collector', [
            'template' => 'PlaybloomGuzzleBundle:Collector:guzzle',
            'id' => 'guzzle'
        ]);
        $container->setDefinition('data_collector.guzzle', $definition);
    }
}
