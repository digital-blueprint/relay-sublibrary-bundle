<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use DBP\API\AlmaBundle\DbpAlmaBundle;
use DBP\API\BaseBundle\DbpBaseBundle;
use DBP\API\CoreBundle\DbpCoreBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new NelmioCorsBundle();
        yield new MonologBundle();
        yield new ApiPlatformBundle();
        yield new DbpBaseBundle();
        yield new DbpCoreBundle();
        yield new DbpAlmaBundle();
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import('@DbpCoreBundle/Resources/config/routing.yaml');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('framework', [
            'test' => true,
            'secret' => '',
        ]);
    }
}
