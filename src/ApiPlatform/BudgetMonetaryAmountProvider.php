<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotFoundException;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Dbp\Relay\SublibraryBundle\Sublibrary\SublibraryProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProviderInterface<BudgetMonetaryAmount>
 */
final class BudgetMonetaryAmountProvider implements ProviderInterface
{
    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    /** @var AlmaApi */
    private $api;

    public function __construct(
        SublibraryProviderInterface $libraryProvider,
        AlmaApi $api
    ) {
        $this->libraryProvider = $libraryProvider;
        $this->api = $api;
    }

    /**
     * @return WholeResultPaginator<BudgetMonetaryAmount>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $api = $this->api;
        $api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            return null;
        }

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

        /**
         * @var WholeResultPaginator<BudgetMonetaryAmount>
         */
        return new WholeResultPaginator($budgetMonetaryAmounts,
            Pagination::getCurrentPageNumber($filters),
            Pagination::getMaxNumItemsPerPage($filters));
    }
}
