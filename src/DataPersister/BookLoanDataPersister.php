<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Service\AlmaApi;

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
     * @param BookLoan $data
     *
     * @return BookLoan
     */
    public function persist($data)
    {
        $bookLoan = $data;
        $api = $this->api;
        $api->checkPermissions();
        $api->updateBookLoan($bookLoan);

        return $bookLoan;
    }

    /**
     * @param BookLoan $data
     */
    public function remove($data)
    {
    }
}
