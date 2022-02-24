<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BudgetMonetaryAmount;
use DBP\API\AlmaBundle\Helpers\ItemNotFoundException;
use DBP\API\AlmaBundle\Service\AlmaApi;
use Dbp\Relay\BaseOrganizationBundle\API\OrganizationProviderInterface;
use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use League\Uri\Contracts\UriException;

final class BudgetMonetaryAmountCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $organizationProvider;

    private $personProvider;

    private $api;

    public function __construct(
        OrganizationProviderInterface $organizationProvider,
        PersonProviderInterface $personProvider,
        AlmaApi $api
    ) {
        $this->organizationProvider = $organizationProvider;
        $this->personProvider = $personProvider;
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BudgetMonetaryAmount::class === $resourceClass;
    }

    /**
     * @throws UriException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $api = $this->api;
        $api->checkPermissions();

        $filters = $context['filters'] ?? [];
        $organizationId = $filters['organization'] ?? '';

        $matches = [];
        if (!preg_match('/^(\w+-F\w+)$/i', $organizationId, $matches)) {
            throw new ItemNotFoundException(sprintf("BudgetMonetaryAmounts for organization id '%s' could not be found!", $organizationId));
        }

        // load organization
        $organizationId = $matches[1];
        $organization = $this->organizationProvider->getOrganizationById($organizationId, ['lang' => 'en']);

        // check permissions of current user to organization
        $this->api->checkOrganizationPermissions($organization);

        // fetch budget monetary amounts of organization
        $budgetMonetaryAmounts = $api->getBudgetMonetaryAmountsByOrganization($organization);

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        return new ArrayFullPaginator($budgetMonetaryAmounts, $page, $perPage);
    }
}
