<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\API;

use Dbp\Relay\CoreBundle\Exception\ApiError;

interface SublibraryProviderInterface
{
    /**
     * Returns the sub-library with the given ID.
     *
     * @throws ApiError
     */
    public function getSublibrary(string $identifier, array $options = []): ?SublibraryInterface;
}
