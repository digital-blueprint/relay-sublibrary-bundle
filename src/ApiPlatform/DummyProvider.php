<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;

class DummyProvider implements ProviderInterface
{
    /**
     * @return array|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return null;
        }

        return [];
    }
}
