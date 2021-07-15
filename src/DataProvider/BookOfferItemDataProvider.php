<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaApi;

final class BookOfferItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookOffer::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?BookOffer
    {
        $api = $this->api;
        $api->checkPermissions();

        $bookOffer = $api->getBookOffer($id);

        // check for the user's permissions to the requested book offer for certain item operations
        switch ($operationName) {
            case 'post_loan':
            case 'get_loans':
            case 'post_return':
                $api->checkBookOfferPermissions($bookOffer);
                break;
        }

        return $bookOffer;
    }
}
