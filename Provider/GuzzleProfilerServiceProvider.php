<?php

namespace Playbloom\Bundle\GuzzleBundle\Provider;

use Guzzle\Log\MonologLogAdapter;
use Guzzle\Plugin\History\HistoryPlugin;
use Guzzle\Plugin\Log\LogPlugin;
use Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Web Profiler provider for guzzle.
 *
 * @author Michael Williams <michael.wiliams@limelyte.com>
 */
class GuzzleProfilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['data_collector.templates'])) {
            throw new \LogicException('The provider: "'.__CLASS__.'" must be registered after the "WebProfilerServiceProvider"');
        }

//        $app->extend('data_collector.templates', function($value) {
//            var_dump($value);
//        });

        $dataCollectorTemplates = $app->raw('data_collector.templates');
        $dataCollectorTemplates[] = array('guzzle', '@PlaybloomGuzzle/Collector/guzzle.html.twig');
        $app['data_collector.templates'] = $dataCollectorTemplates;

        $app['playbloom_guzzle.client.plugin.profiler'] = $app->share(function($app) {
            $plugin = new HistoryPlugin();
            $plugin->setLimit(100);

            return $plugin;
        });

        $dataCollectors = $app->raw('data_collectors');
        $dataCollectors['guzzle'] = $app->share(function($app) {
            return new GuzzleDataCollector($app['playbloom_guzzle.client.plugin.profiler']);
        });
        $app['data_collectors'] = $dataCollectors;

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
           $loader->addPath($app['playbloom_guzzle.profiler.templates_path'], 'PlaybloomGuzzle');

           return $loader;
       }));

       $app['playbloom_guzzle.profiler.templates_path'] = function () {
           $r = new \ReflectionClass('Playbloom\Bundle\GuzzleBundle\DataCollector\GuzzleDataCollector');

           return dirname(dirname($r->getFileName())).'/Resources/views';
       };
    }

    public function boot(Application $app)
    {
    }
}
