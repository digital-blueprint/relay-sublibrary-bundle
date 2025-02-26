<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\SublibraryBundle\API\SublibraryInterface;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\ApiPlatform\Sublibrary;

class DummySublibraryProvider implements SublibraryProviderInterface
{
    /**
     * Returns the sub-library with the given ID.
     *
     * @throws ApiError
     */
    public function getSublibrary(string $identifier, array $options = []): ?SublibraryInterface
    {
        $lib = new Sublibrary();
        $lib->setIdentifier($identifier);
        $lib->setCode($identifier);

        return $lib;
    }
}
