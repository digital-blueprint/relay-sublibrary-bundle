<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Dbp\Relay\SublibraryBundle\Entity\BookLoan;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookLoanDataPersister implements DataPersisterInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports($data): bool
    {
        return $data instanceof BookLoan;
    }

    /**
     * Updates a book loan in Alma.
     *
     * We haven't manage to get "object" and "borrower" in a POST request, they always were set to "null"
     * So we are using a "PostBookLoanByBookOffer" controller for creating loans
     *
     * @param mixed $data
     *
     * @return BookLoan
     */
    public function persist($data)
    {
        assert($data instanceof BookLoan);

        $bookLoan = $data;
        $api = $this->api;
        $api->checkPermissions();
        $api->updateBookLoan($bookLoan);

        return $bookLoan;
    }

    /**
     * @param mixed $data
     */
    public function remove($data)
    {
    }
}
