<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Doctrine\Common\Collections\ArrayCollection;

class GetLocationIdentifiersByBookOffer extends AlmaController
{
    public function __invoke(BookOffer $data): ArrayCollection
    {
        $this->api->checkPermissions();

        $locationIdentifiers = $this->api->locationIdentifiersByBookOffer($data);

        return $locationIdentifiers;
    }
}
