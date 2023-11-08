<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotFoundException;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Response;

final class BudgetMonetaryAmountProvider implements ProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

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

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
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
