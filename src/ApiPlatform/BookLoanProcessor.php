<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookLoanProcessor implements ProcessorInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * Updates a book loan in Alma.
     *
     * We haven't managed to get "object" and "borrower" in a POST request, they always were set to "null"
     * So we are using a "PostBookLoanByBookOffer" controller for creating loans
     *
     * @param mixed $data
     *
     * @return BookLoan
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $api = $this->api;
        $api->checkPermissions();

        assert($data instanceof BookLoan);
        $bookLoan = $data;

        if ($operation instanceof Put) {
            $api->updateBookLoan($bookLoan);
        }

        return $bookLoan;
    }
}
