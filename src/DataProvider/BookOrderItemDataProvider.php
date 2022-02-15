<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookOrder;
use DBP\API\AlmaBundle\Helpers\ItemNotFoundException;
use DBP\API\AlmaBundle\Service\AlmaApi;

final class BookOrderItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $almaApi;

    public function __construct(AlmaApi $almaApi)
    {
        $this->almaApi = $almaApi;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookOrder::class === $resourceClass;
    }

    /**
     * Fetches a book order from the list of book orders on an organization (there is no valid other api to do this).
     *
     * @throws ItemNotFoundException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?BookOrder
    {
        $this->almaApi->checkPermissions();

        return $this->almaApi->getBookOrder($id);
    }
}
