<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByOrganization extends AlmaController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(string $identifier): ArrayCollection
    {
        $this->checkPermissions();

        $org = $this->orgProvider->getOrganizationById($identifier, 'en');
        $this->api->checkOrganizationPermissions($org);
        $this->api->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->api->addAllBookLoansByOrganizationToCollection($org, $collection);

        return $collection;
    }
}
