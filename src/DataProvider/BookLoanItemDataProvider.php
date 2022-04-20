<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\BookLoan;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookLoanItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return BookLoan::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?BookLoan
    {
        $api = $this->api;
        $api->checkPermissions();

        $data = $api->getBookLoanJsonData($id);
        $bookLoan = $api->bookLoanFromJsonItem($data);
        $bookOffer = $bookLoan->getObject();

        // check for the user's permissions to the book offer of the requested book loan
        $api->checkCurrentPersonBookOfferPermissions($bookOffer);

        return $bookLoan;
    }
}
