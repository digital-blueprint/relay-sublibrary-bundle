<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookOrder;
use DBP\API\AlmaBundle\Helpers\ItemNotFoundException;
use DBP\API\AlmaBundle\Service\AlmaApi;
use Dbp\Relay\BaseOrganizationBundle\API\OrganizationProviderInterface;
use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;

final class BookOrderItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $orgaProvider;

    private $almaApi;

    private $personProvider;

    public function __construct(OrganizationProviderInterface $orgaProvider, PersonProviderInterface $personProvider, AlmaApi $almaApi)
    {
        $this->orgaProvider = $orgaProvider;
        $this->personProvider = $personProvider;
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

        $matches = [];
        if (!preg_match('/^o-(\w+-F\w+)-(.+)$/i', $id, $matches)) {
            throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
        }

        // load organization
        $organizationId = $matches[1];
        $organization = $this->orgaProvider->getOrganizationById($organizationId, 'de');

        // check permissions of current user to organization
        $this->almaApi->checkOrganizationPermissions($organization);

        // fetch all book orders of the organization
        $collection = new ArrayCollection();
        $this->almaApi->addAllBookOrdersByOrganizationToCollection($organization, $collection);

        // search for the correct book order in the collection of book orders
        /** @var BookOrder $bookOrder */
        foreach ($collection as $bookOrder) {
            if ($bookOrder->getIdentifier() === $id) {
                return $bookOrder;
            }
        }

        throw new ItemNotFoundException(sprintf("BookOrder with id '%s' could not be found!", $id));
    }
}
