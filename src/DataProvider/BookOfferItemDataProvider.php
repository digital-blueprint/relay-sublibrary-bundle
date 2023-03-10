<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

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
                $api->checkCurrentPersonBookOfferPermissions($bookOffer);
                break;
        }

        return $bookOffer;
    }
}
