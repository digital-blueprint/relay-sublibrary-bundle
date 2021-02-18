<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetBookLoansByBookOffer extends AlmaController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(BookOffer $data): ArrayCollection
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $jsonData = $this->api->getBookLoansJsonDataByBookOffer($data);
        $collection = new ArrayCollection();

        foreach ($jsonData as $item) {
            $bookLoan = $this->api->bookLoanFromJsonItem($item);
            $collection->add($bookLoan);
        }

        return $collection;
    }
}
