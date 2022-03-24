<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Dbp\Relay\SublibraryBundle\Entity\BookOffer;

class PostReturnByBookOffer extends AlmaController
{
    public function __invoke(string $identifier): BookOffer
    {
        $this->api->checkPermissions();

        $bookOffer = $this->api->getBookOffer($identifier);
        $this->api->returnBookOffer($bookOffer);

        // returnBookOffer doesn't return anything so we just return the book offer we got instead
        return $bookOffer;
    }
}
