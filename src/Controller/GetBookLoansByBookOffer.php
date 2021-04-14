<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Exception\ItemNotLoadedException;

class GetBookLoansByBookOffer extends AlmaController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(string $id): array
    {
        $this->checkPermissions();

        $bookOffer = $this->api->getBookOffer($id);
        $jsonData = $this->api->getBookLoansJsonDataByBookOffer($bookOffer);
        $bookLoans = [];

        foreach ($jsonData as $item) {
            $bookLoan = $this->api->bookLoanFromJsonItem($item);
            $bookLoans[] = $bookLoan;
        }

        return $bookLoans;
    }
}
