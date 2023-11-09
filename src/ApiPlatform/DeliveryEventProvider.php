<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class DeliveryEventProvider implements ProviderInterface
{
    /**
     * @return null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return null;
    }
}
