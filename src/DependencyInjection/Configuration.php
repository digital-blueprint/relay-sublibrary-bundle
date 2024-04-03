<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const LDAP_CONNECTION_ATTRIBUTE = 'connection';
    public const LDAP_ALMA_USER_ID_ATTRIBUTE_ATTRIBUTE = 'alma_user_id_attribute';

    public const ROLE_LIBRARY_MANAGER = 'ROLE_LIBRARY_MANAGER';
    public const SUBLIBRARY_IDS = 'SUBLIBRARY_IDS';
    public const ALMA_LIBRARY_IDS = 'ALMA_LIBRARY_IDS';

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

        $rootNode->append($this->getAuthorizationNode());

        return $treeBuilder;
    }

    private function getAuthorizationNode(): NodeDefinition
    {
        return AuthorizationConfigDefinition::create()
            ->addPolicy(self::ROLE_LIBRARY_MANAGER, 'false', 'Returns true if the user is allowed to use the dispatch API.')
            ->addAttribute(self::SUBLIBRARY_IDS, '[]', 'Returns the list of sublibrary IDs the user has manager rights in')
            ->addAttribute(self::ALMA_LIBRARY_IDS, '[]', 'Returns the list of Alma library IDs the user has manager rights in')
            ->getNodeDefinition();
    }
}
