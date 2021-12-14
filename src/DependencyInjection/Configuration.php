<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_alma');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('api_url')->end()
            ->scalarNode('api_key')->end()
            ->scalarNode('analytics_api_key')->end()
            ->booleanNode('readonly')->defaultFalse()->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
