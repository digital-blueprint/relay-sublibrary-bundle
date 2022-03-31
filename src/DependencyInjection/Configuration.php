<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_sublibrary');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('api_url')->end()
            ->scalarNode('api_key')->end()
            ->scalarNode('analytics_api_key')->end()
            ->booleanNode('readonly')->defaultFalse()->end()
            ->end()
            ->end()
        ;

        $ldapBuilder = new TreeBuilder('ldap');
        $ldapNode = $ldapBuilder->getRootNode()
            ->children()
            ->scalarNode('host')->end()
            ->scalarNode('base_dn')->end()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
            ->scalarNode('encryption')->end()
            ->end();

        $attributesBuilder = new TreeBuilder('attributes');
        $attributesNode = $attributesBuilder->getRootNode()
            ->children()
            ->scalarNode('identifier')->end()
            ->scalarNode('alma_user_id')->end()
            ->end();
        $ldapNode->append($attributesNode);

        $rootNode->append($ldapNode);

        return $treeBuilder;
    }
}
