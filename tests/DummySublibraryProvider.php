<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\SublibraryBundle\Sublibrary\SublibraryInterface;
use Dbp\Relay\SublibraryBundle\Sublibrary\SublibraryProviderInterface;

class DummySublibraryProvider implements SublibraryProviderInterface
{
    /**
     * Returns the sub-library with the given ID.
     *
     * @throws ApiError
     */
    public function getSublibrary(string $identifier, array $options = []): ?SublibraryInterface
    {
        $lib = new DummySublibrary();
        $lib->setIdentifier($identifier);
        $lib->setCode($identifier);
        $lib->setName($identifier);

        return $lib;
    }
}
