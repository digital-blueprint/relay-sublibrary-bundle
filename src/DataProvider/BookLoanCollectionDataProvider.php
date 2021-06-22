<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Exception\ApiError;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;
use Symfony\Component\HttpFoundation\Response;

final class BookLoanCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookLoan::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $api = $this->api;
        $api->checkPermissions();

        $filters = $context['filters'] ?? [];
        try {
            $bookOffers = $api->getBookLoans($filters);
        } catch (\Exception $e) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }
        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($filters['page'])) {
            $page = (int) $filters['page'];
        }

        if (isset($filters['perPage'])) {
            $perPage = (int) $filters['perPage'];
        }

        // TODO: do pagination via API
        return new ArrayFullPaginator($bookOffers, $page, $perPage);
    }
}
