<?php

namespace DBP\API\AlmaBundle\Controller;

use App\Entity\Person;
use App\Exception\ItemNotLoadedException;
use App\Exception\ItemNotStoredException;
use App\Exception\ItemNotUsableException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByPerson extends AlmaController
{
    /**
     * @param Person $data
     * @return ArrayCollection
     * @throws ItemNotStoredException
     * @throws ItemNotLoadedException
     * @throws ItemNotUsableException
     */
    public function __invoke(Person $data): ArrayCollection
    {
        $jsonData = $this->api->getBookLoansJsonDataByPerson($data);
        $collection = new ArrayCollection();

        foreach ($jsonData as $item)
        {
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
