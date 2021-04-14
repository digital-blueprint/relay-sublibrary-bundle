<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByPerson extends AlmaController
{
    /**
     * @throws ItemNotStoredException
     * @throws ItemNotLoadedException
     * @throws ItemNotUsableException
     */
    public function __invoke(string $id): ArrayCollection
    {
        $this->checkPermissions();

        $person = $this->personProvider->getPerson($id);
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
