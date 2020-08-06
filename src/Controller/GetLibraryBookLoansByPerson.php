<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Entity\Person;
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
    public function __invoke(Person $data): ArrayCollection
    {
        $jsonData = $this->api->getBookLoansJsonDataByPerson($data);
        $collection = new ArrayCollection();

        foreach ($jsonData as $item) {
            $bookLoan = $this->api->bookLoanFromJsonItem($item);
            $bookOffer = $bookLoan->getObject();

            // only return loans where the user has permissions to
            if ($this->api->checkBookOfferPermissions($bookOffer, false)) {
                $collection->add($bookLoan);
            }
        }

        return $collection;
    }
}
