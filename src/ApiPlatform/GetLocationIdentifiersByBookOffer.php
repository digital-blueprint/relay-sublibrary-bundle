<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Dbp\Relay\SublibraryBundle\Helpers\ItemNotLoadedException;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use League\Uri\Contracts\UriException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GetLocationIdentifiersByBookOffer extends AbstractController
{
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @throws ItemNotLoadedException|UriException
     */
    public function __invoke(string $identifier): array
    {
        $this->api->checkPermissions();

        return $this->api->locationIdentifiersByBookOffer($this->api->getBookOffer($identifier));
    }
}
