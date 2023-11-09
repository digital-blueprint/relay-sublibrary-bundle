<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Dbp\Relay\SublibraryBundle\Controller\AlmaController;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotLoadedException;
use League\Uri\Contracts\UriException;

class GetLocationIdentifiersByBookOffer extends AlmaController
{
    /**
     * @throws ItemNotLoadedException|UriException
     */
    public function __invoke(string $identifier): array
    {
        $this->api->checkPermissions();

        return $this->api->locationIdentifiersByBookOffer($this->api->getBookOffer($identifier));
    }
}
