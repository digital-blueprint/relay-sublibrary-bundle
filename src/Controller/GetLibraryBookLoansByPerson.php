<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByPerson extends AlmaController
{
    public function __invoke(string $identifier): ArrayCollection
    {
        $this->api->checkPermissions();

        $person = $this->personProvider->getPerson($identifier);
        $jsonData = $this->api->getBookLoansJsonDataByPerson($person);

        $bookLoans = [];
        foreach ($jsonData as $item) {
            $bookLoans[] = $this->api->bookLoanFromJsonItem($item);
        }
        // only return the ones the user has permissions to
        $bookLoans = $this->api->filterBookLoans($bookLoans);

        return new ArrayCollection($bookLoans);
    }
}
