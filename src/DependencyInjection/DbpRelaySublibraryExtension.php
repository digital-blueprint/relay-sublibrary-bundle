<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelaySublibraryExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

    public function prepend(ContainerBuilder $container)
    {
        $this->addExposeHeader($container, 'X-Analytics-Update-Date');
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $this->addResourceClassDirectory($container, __DIR__.'/../Entity');

        $pathsToHide = [
            '/sublibrary/delivery-statuses/{identifier}',
            '/sublibrary/parcel-deliveries/{identifier}',
            '/sublibrary/book-order-items/{identifier}',
            '/sublibrary/event-status-types/{identifier}',
            '/sublibrary/sublibraries/{identifier}',
            '/sublibrary/books',
            '/sublibrary/budget-monetary-amounts/{identifier}',
        ];
        foreach ($pathsToHide as $path) {
            $this->addPathToHide($container, $path);
        }

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
}
