<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookOrdersByOrganization extends OrganizationController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(Organization $data): ArrayCollection
    {
        $this->checkOrganizationPermissions($data);
        $this->almaApi->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->almaApi->addAllBookOrdersByOrganizationToCollection($data, $collection);

        return $collection;
    }
}
