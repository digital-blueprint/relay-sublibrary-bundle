<?php

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Helpers\ArrayFullPaginator;

final class BookOfferCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookOffer::class === $resourceClass;
    }

    /**
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
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
