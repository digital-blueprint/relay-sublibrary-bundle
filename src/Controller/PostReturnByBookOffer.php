<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\BookOffer;

class PostReturnByBookOffer extends AlmaController
{
    public function __invoke(string $identifier): BookOffer
    {
        $this->checkPermissions();

        $bookOffer = $this->api->getBookOffer($identifier);
        $this->api->returnBookOffer($bookOffer);

        // returnBookOffer doesn't return anything so we just return the book offer we got instead
        return $bookOffer;
    }
}
