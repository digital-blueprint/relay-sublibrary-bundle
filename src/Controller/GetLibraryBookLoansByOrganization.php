<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Dbp\Relay\BaseOrganizationBundle\Entity\Organization;
use Doctrine\Common\Collections\ArrayCollection;

class GetLibraryBookLoansByOrganization extends AlmaController
{
    public function __invoke(string $identifier): ArrayCollection
    {
        $this->api->checkPermissions();

        //$org = $this->orgProvider->getOrganizationById($identifier, ['lang' => 'en']);

        $library = $this->libraryProvider->getSublibrary($identifier, ['lang' => 'en']);

        dump($library);

        $org = new Organization();
        $org->setIdentifier($library->getIdentifier());
        $org->setName($library->getName());

        $this->api->checkOrganizationPermissions($org);
        $this->api->setAnalyticsUpdateDateHeader();

        $collection = new ArrayCollection();
        $this->api->addAllBookLoansByOrganizationToCollection($org, $collection);

        return $collection;
    }
}
