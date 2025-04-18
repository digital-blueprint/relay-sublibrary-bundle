<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DependencyInjection;

use Dbp\Relay\CoreBundle\Extension\ExtensionTrait;
use Dbp\Relay\SublibraryBundle\Authorization\AuthorizationService;
use Dbp\Relay\SublibraryBundle\Service\AlmaPersonProvider;
use Dbp\Relay\SublibraryBundle\Service\ConfigurationService;
use Dbp\Relay\SublibraryBundle\Sublibrary\SublibraryProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class DbpRelaySublibraryExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    use ExtensionTrait;

    public function prepend(ContainerBuilder $container): void
    {
        $this->addExposeHeader($container, 'X-Analytics-Update-Date');
    }

    public function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $this->addResourceClassDirectory($container, __DIR__.'/../ApiPlatform');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');

        $definition = $container->getDefinition(AuthorizationService::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(ConfigurationService::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(AlmaPersonProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);

        $definition = $container->getDefinition(SublibraryProvider::class);
        $definition->addMethodCall('setConfig', [$mergedConfig]);
    }
}
