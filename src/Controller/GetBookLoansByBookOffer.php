<?php

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\BookOffer;
use App\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetBookLoansByBookOffer extends AlmaController
{
    /**
     * @param BookOffer $data
     * @return ArrayCollection
     * @throws ItemNotLoadedException
     */
    public function __invoke(BookOffer $data): ArrayCollection
    {
        $jsonData = $this->api->getBookLoansJsonDataByBookOffer($data);
        $collection = new ArrayCollection();

        foreach ($jsonData as $item)
        {
            $bookLoan = $this->api->bookLoanFromJsonItem($item);
            $collection->add($bookLoan);
        }

        return $collection;
    }
}
