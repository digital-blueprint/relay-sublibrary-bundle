<?php

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use DBP\API\AlmaBundle\Entity\BookOrder;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Service\TUGOnlineApi;
use Doctrine\Common\Collections\ArrayCollection;

final class BookOrderItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    protected $tugOnlineApi;

    protected $almaApi;

    public function __construct(TUGOnlineApi $tugOnlineApi, AlmaApi $almaApi)
    {
        $this->tugOnlineApi = $tugOnlineApi;
        $this->almaApi = $almaApi;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookOrder::class === $resourceClass;
    }

    /**
     * Fetches a book order from the list of book orders on an organization (there is no valid other api to do this).
     *
     * @param array|int|string $id
     *
     * @throws ItemNotLoadedException
     * @throws ItemNotFoundException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?BookOrder
    {
        // we need to urldecode because of the encoded order number part
        $id = urldecode($id);

        $matches = [];
        if (!preg_match('/^kbo-(\w+-F\w+)-(.+)$/i', $id, $matches)) {
            throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
        }

        // load organizaiton
        $organizationId = $matches[1];
        $organization = $this->tugOnlineApi->getOrganizationById($organizationId);

        // check permissions of current user to organization
        $this->tugOnlineApi->checkOrganizationPermissions($organization);

        // fetch all book orders of the organization
        $collection = new ArrayCollection();
        $this->almaApi->addAllBookOrdersByOrganizationToCollection($organization, $collection);

        // search for the correct book order in the collection of book orders
        /** @var BookOrder $bookOrder */
        foreach ($collection as $bookOrder) {
            if ($bookOrder->getIdentifier() == $id) {
                return $bookOrder;
            }
        }

        throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
    }
}
