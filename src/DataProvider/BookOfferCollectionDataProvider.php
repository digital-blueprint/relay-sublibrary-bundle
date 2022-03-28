<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookOfferCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookOffer::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $api = $this->api;
        $api->checkPermissions();

        $filters = $context['filters'] ?? [];
        $bookOffers = $api->getBookOffers($filters);

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        // TODO: do pagination via API
        $pagination = new ArrayFullPaginator($bookOffers, $page, $perPage);

        return $pagination;
    }
}
