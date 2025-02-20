<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Authorization\AuthorizationConfigDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROLE_LIBRARY_MANAGER = 'ROLE_LIBRARY_MANAGER';
    public const SUBLIBRARY_IDS = 'SUBLIBRARY_IDS';
    public const ALMA_LIBRARY_IDS = 'ALMA_LIBRARY_IDS';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dbp_relay_sublibrary');
        $rootNode = $treeBuilder->getRootNode();

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_url')
                    ->info('The REST API endpoint to use')
                    ->example('https://api-eu.hosted.exlibrisgroup.com')
                    ->isRequired()
                ->end()
                ->scalarNode('api_key')
                    ->info('The API key for the REST API')
                    ->example('your_key')
                    ->isRequired()
                ->end()
                ->scalarNode('analytics_api_key')
                    ->info('The API key for the analytics API')
                    ->example('your_key')
                    ->isRequired()
                ->end()
                ->booleanNode('readonly')
                    ->info('Makes all write operations error out, even if the API key would allow them.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('analytics_reports')
                    ->addDefaultsIfNotSet()
                    ->info('The full paths to the used analytics reports')
                    ->children()
                        ->scalarNode('book_offer')
                            ->info('Full path to the report containing information about all book offers')
                            ->defaultValue('/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Bestand-Institute-pbeke')
                        ->end()
                        ->scalarNode('book_order')
                            ->info('Full path to the report containing information about all book orders')
                            ->defaultValue('/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/PO-List-pbeke_bearb_SF_6c')
                        ->end()
                        ->scalarNode('book_loan')
                            ->info('Full path to the report containing information about all book loans')
                            ->defaultValue('/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Ausleihen-Institute-pbeke')
                        ->end()
                        ->scalarNode('budget')
                            ->info('Full path to the report containing information about the budget of the libraries')
                            ->defaultValue('/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Funds-List-SF_2')
                        ->end()
                        ->scalarNode('update_check')
                            ->info('Full path to the report containing information about when the analytics were last updated')
                            ->defaultValue('/shared/Technische Universität Graz 43ACC_TUG/Reports/vpu/Analytics-Updates')
                        ->end()
                    ->end()
                ->end()
            ->end()
       ->end()
        ;

        $rootNode->append($this->getAuthorizationNode());

        return $treeBuilder;
    }

    private function getAuthorizationNode(): NodeDefinition
    {
        return AuthorizationConfigDefinition::create()
            ->addRole(self::ROLE_LIBRARY_MANAGER, 'false', 'Returns true if the user is allowed to use the dispatch API.')
            ->addAttribute(self::SUBLIBRARY_IDS, '[]', 'Returns the list of sublibrary IDs the user has manager rights in')
            ->addAttribute(self::ALMA_LIBRARY_IDS, '[]', 'Returns the list of Alma library IDs the user has manager rights in')
            ->getNodeDefinition();
    }
}
