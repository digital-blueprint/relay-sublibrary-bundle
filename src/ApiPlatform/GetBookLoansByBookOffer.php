<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetBookLoansByBookOffer extends AbstractController
{
    /** @var AlmaApi */
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(string $identifier): array
    {
        $this->api->checkPermissions();

        $bookOffer = $this->api->getBookOffer($identifier);
        $jsonData = $this->api->getBookLoansJsonDataByBookOffer($bookOffer);
        $bookLoans = [];

        foreach ($jsonData as $item) {
            $bookLoan = $this->api->bookLoanFromJsonItem($item);
            $bookLoans[] = $bookLoan;
        }

        return $bookLoans;
    }
}
