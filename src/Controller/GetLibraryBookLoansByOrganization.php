<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByOrganization extends AlmaController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(Organization $data): ArrayCollection
    {
        $this->checkPermissions();

        $this->api->checkOrganizationPermissions($data);
        $this->api->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->api->addAllBookLoansByOrganizationToCollection($data, $collection);

        return $collection;
    }
}
