<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\BudgetMonetaryAmount;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotFoundException;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use League\Uri\Contracts\UriException;
use Symfony\Component\HttpFoundation\Response;

final class BudgetMonetaryAmountCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    /** @var PersonProviderInterface */
    private $personProvider;

    /** @var AlmaApi */
    private $api;

    public function __construct(
        SublibraryProviderInterface $libraryProvider,
        PersonProviderInterface $personProvider,
        AlmaApi $api
    ) {
        $this->libraryProvider = $libraryProvider;
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

        $libraryId = $filters['sublibrary'] ?? null;
        if (empty($libraryId)) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, "parameter 'sublibrary' is mandatory!");
        }

        $library = $this->libraryProvider->getSublibrary($libraryId);
        if ($library === null) {
            throw new ItemNotFoundException("library with id '".$libraryId."' not found!");
        }
        $api->checkCurrentPersonLibraryPermissions($library);

        // fetch budget monetary amounts of organization
        $budgetMonetaryAmounts = $api->getBudgetMonetaryAmountsByLibrary($library);

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
