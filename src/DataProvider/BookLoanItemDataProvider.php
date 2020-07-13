<?php
namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Service\AlmaApi;

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

    /**
     * @param string $resourceClass
     * @param array|int|string $id
     * @param string|null $operationName
     * @param array $context
     * @return BookLoan|null
     * @throws \App\Exception\ItemNotLoadedException
     * @throws \App\Exception\ItemNotStoredException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?BookLoan
    {
        $api = $this->api;
        $api->checkPermissions();

        $data = $api->getBookLoanJsonData($id);
        $bookLoan = $api->bookLoanFromJsonItem($data);
        $bookOffer = $bookLoan->getObject();

        // check for the user's permissions to the book offer of the requested book loan
        $api->checkBookOfferPermissions($bookOffer);

        return $bookLoan;
    }
}
