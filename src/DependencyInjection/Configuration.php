<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final readonly class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('deserializing_connection');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('profiling')
                    ->defaultTrue()
                    ->info('Enables profiling of all usages for the Symfony profiler. Only has an effect when the profiler is available.')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
