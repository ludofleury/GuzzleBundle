<?php

namespace Playbloom\Bundle\GuzzleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler\ClientPluginPass;
use Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler\DataCollectorPass;

class PlaybloomGuzzleBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ClientPluginPass());
        // $container->addCompilerPass(new DataCollectorPass());
    }
}
