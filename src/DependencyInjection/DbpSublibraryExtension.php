<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpSublibraryExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $this->extendArrayParameter(
            $container, 'dbp_api.expose_headers', [
                'X-Analytics-Update-Date', ]
        );
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__.'/../Entity']);

        $this->extendArrayParameter(
            $container, 'dbp_api.paths_to_hide', [
            '/sublibrary/delivery_statuses/{identifier}',
            '/sublibrary/parcel_deliveries/{identifier}',
            '/sublibrary/book_order_items/{identifier}',
            '/sublibrary/event_status_types/{identifier}',
            '/library_budget_monetary_amounts/{identifier}',
        ]);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $cacheDef = $container->register('dbp_api.cache.alma.analytics', FilesystemAdapter::class);
        $cacheDef->setArguments(['alma-analytics', 60, '%kernel.cache_dir%/dbp/alma-analytics']);
        $cacheDef->setPublic(true);
        $cacheDef->addTag('cache.pool');

        $ldapCache = $container->register('dbp_api.cache.alma.ldap', FilesystemAdapter::class);
        $ldapCache->setArguments(['core-ldap', 360, '%kernel.cache_dir%/dbp/alma-ldap']);
        $ldapCache->setPublic(true);
        $ldapCache->addTag('cache.pool');

        $definition = $container->getDefinition('Dbp\Relay\SublibraryBundle\Service\LDAPApi');
        $definition->addMethodCall('setConfig', [$mergedConfig['ldap'] ?? []]);
        $definition->addMethodCall('setLDAPCache', [$ldapCache, 360]);

        $definition = $container->getDefinition('Dbp\Relay\SublibraryBundle\Service\AlmaApi');
        $definition->addMethodCall('setConfig', [$mergedConfig]);
        $definition->addMethodCall('setCache', [$cacheDef]);
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values)
    {
        if (!$container->hasParameter($parameter)) {
            $container->setParameter($parameter, []);
        }
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}
