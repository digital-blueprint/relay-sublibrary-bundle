<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

class GetBookLoansByBookOffer extends AlmaController
{
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
