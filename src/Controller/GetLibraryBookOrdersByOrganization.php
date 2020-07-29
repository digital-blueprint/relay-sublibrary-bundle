<?php

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookOrdersByOrganization extends OrganizationController
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
        $this->almaApi->addAllBookOrdersByOrganizationToCollection($data, $collection);

        return $collection;
    }
}
