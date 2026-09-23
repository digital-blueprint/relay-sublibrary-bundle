<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\BaseOrganizationBundle\DbpRelayBaseOrganizationBundle;
use Dbp\Relay\CoreBundle\TestUtils\CoreTestKernelTrait;
use Dbp\Relay\SublibraryBundle\DbpRelaySublibraryBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use CoreTestKernelTrait;

    protected function registerAdditionalBundles(): iterable
    {
        yield new DbpRelayBaseOrganizationBundle();
        yield new DbpRelaySublibraryBundle();
    }

    protected function configureAdditionalContainer(ContainerConfigurator $container): void
    {
        $container->extension('dbp_relay_sublibrary', [
            'api_url' => '',
            'api_key' => '',
            'analytics_api_key' => '',
        ]);
    }
}
