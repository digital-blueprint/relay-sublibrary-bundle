<?php

namespace DBP\API\AlmaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class AlmaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->extendArrayParameter(
            $container, 'api_platform.resource_class_directories', [__DIR__ . '/../Entity']);

        $this->extendArrayParameter(
            $container, 'dbp_api.paths_to_hide', [
            "/delivery_statuses/{id}",
            "/parcel_deliveries/{id}",
            "/order_items/library_book_order_items/{id}",
            "/event_status_types/{id}"
        ]);

        $def = $container->register('dbp_api.cache.alma.analytics', FilesystemAdapter::class);
        $def->setArguments(['dbp_api.cache.alma.analytics', 60, '%kernel.cache_dir%/dbp/alma-analytics']);
        $def->setPublic(true);
        $def->addTag("cache.pool");

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    private function extendArrayParameter(ContainerBuilder $container, string $parameter, array $values) {
        if (!$container->hasParameter($parameter))
            $container->setParameter($parameter, []);
        $oldValues = $container->getParameter($parameter);
        assert(is_array($oldValues));
        $container->setParameter($parameter, array_merge($oldValues, $values));
    }
}