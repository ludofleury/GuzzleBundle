<?php

namespace Playbloom\Bundle\GuzzleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Playbloom\Bundle\GuzzleBundle\DependencyInjection\Compiler\ClientPluginPass;

/**
 * Playbloom Guzzle Bundle
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class PlaybloomGuzzleBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container Container builder
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ClientPluginPass());
    }
}
