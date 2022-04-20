<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;

class DummySublibraryProvider implements SublibraryProviderInterface
{
    /**
     * Returns the sub-library with the given ID.
     *
     * @throws ApiError
     */
    public function getSublibrary(string $identifier, array $options = []): ?Sublibrary
    {
        return new Sublibrary();
    }

    /**
     * Returns the array of sub-library IDs the given Person is a library manager of.
     *
     * @return string[]
     */
    public function getSublibraryIdsByLibraryManager(Person $person): array
    {
        return [];
    }

    /**
     * Returns the array of sub-library codes the given Person is a library manager of.
     *
     * @return string[]
     */
    public function getSublibraryCodesByLibraryManager(Person $person): array
    {
        return [];
    }

    /*
     * Returns whether the given Person is a library manager of the Sublibrary with the given ID.
     *
     * @param string $sublibraryId The Sublibrary ID.
     */
    public function isLibraryManagerById(Person $person, string $sublibraryId): bool
    {
        return false;
    }

    /*
     * Returns whether the given Person is a library manager of the Sublibrary with the given code.
     *
     * @param string $sublibraryCode The Sublibrary code.
     */
    public function isLibraryManagerByCode(Person $person, string $sublibraryCode): bool
    {
        return false;
    }
}
