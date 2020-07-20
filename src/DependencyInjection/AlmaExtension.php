<?php

namespace DBP\API\AlmaBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AlmaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $dirs = $container->getParameter('api_platform.resource_class_directories');
        $dirs[] = __DIR__ . '/../Entity';
        $container->setParameter('api_platform.resource_class_directories', $dirs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yaml');
    }
}