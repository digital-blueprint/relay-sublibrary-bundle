<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookOffersByOrganization extends AlmaController
{
    public function __invoke(string $identifier): ArrayCollection
    {
        $this->checkPermissions();

        $org = $this->orgProvider->getOrganizationById($identifier, 'en');
        $this->api->checkOrganizationPermissions($org);
        $this->api->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->api->addAllBookOffersByOrganizationToCollection($org, $collection);

        return $collection;
    }
}
