<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

class DummyProvider implements ProviderInterface
{
    /** @var AlmaApi */
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return array|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            return null;
        }

        return [];
    }
}
