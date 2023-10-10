<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
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
            '/sublibrary/book-locations/{identifier}',
            '/sublibrary/book-locations',
        ];
        foreach ($pathsToHide as $path) {
            $this->addPathToHide($container, $path);
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $definition = $container->getDefinition('Dbp\Relay\SublibraryBundle\Service\LDAPApi');
        $definition->addMethodCall('setConfig', [$mergedConfig['ldap'] ?? []]);

        $definition = $container->getDefinition('Dbp\Relay\SublibraryBundle\Service\AlmaApi');
        $definition->addMethodCall('setConfig', [$mergedConfig]);
    }
}
