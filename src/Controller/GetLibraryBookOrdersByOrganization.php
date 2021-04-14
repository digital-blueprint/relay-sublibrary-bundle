<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookOrdersByOrganization extends AlmaController
{
    /**
     * @throws ItemNotLoadedException
     */
    public function __invoke(string $id): ArrayCollection
    {
        $this->checkPermissions();

        $org = $this->orgProvider->getOrganizationById($id, 'en');
        $this->api->checkOrganizationPermissions($org);
        $this->api->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->api->addAllBookOrdersByOrganizationToCollection($org, $collection);

        return $collection;
    }
}
