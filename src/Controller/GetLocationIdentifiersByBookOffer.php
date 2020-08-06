<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\BookOffer;
use Doctrine\Common\Collections\ArrayCollection;

class GetLocationIdentifiersByBookOffer extends AlmaController
{
    /**
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function __invoke(BookOffer $data): ArrayCollection
    {
        $locationIdentifiers = $this->api->locationIdentifiersByBookOffer($data);

        return $locationIdentifiers;
    }
}
