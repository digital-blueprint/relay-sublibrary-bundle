<?php

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\Book;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Entity\BookOffer;
use App\Entity\Person;
use App\Entity\TUGOnline\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use SimpleXMLElement;

class GetLibraryBookLoansByOrganization extends OrganizationController
{
    /**
     * @param Organization $data
     * @return ArrayCollection
     * @throws ItemNotLoadedException
     */
    public function __invoke(Organization $data): ArrayCollection
    {
        $this->tugOnlineApi->checkOrganizationPermissions($data);
        $this->almaApi->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->almaApi->addAllBookLoansByOrganizationToCollection($data, $collection);

        return $collection;
    }
}
