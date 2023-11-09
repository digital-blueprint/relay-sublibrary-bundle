<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\API;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\Exception\ApiError;

interface SublibraryProviderInterface
{
    /**
     * Returns the sub-library with the given ID.
     *
     * @throws ApiError
     */
    public function getSublibrary(string $identifier, array $options = []): ?SublibraryInterface;

    /**
     * Returns the array of sub-library IDs the given Person is a library manager of.
     *
     * @return string[]
     */
    public function getSublibraryIdsByLibraryManager(Person $person): array;

    /**
     * Returns the array of sub-library codes the given Person is a library manager of.
     *
     * @return string[]
     */
    public function getSublibraryCodesByLibraryManager(Person $person): array;

    /*
     * Returns whether the given Person is a library manager of the Sublibrary with the given ID.
     *
     * @param string $sublibraryId The Sublibrary ID.
     */
    public function isLibraryManagerById(Person $person, string $sublibraryId): bool;

    /*
     * Returns whether the given Person is a library manager of the Sublibrary with the given code.
     *
     * @param string $sublibraryCode The Sublibrary code.
     */
    public function isLibraryManagerByCode(Person $person, string $sublibraryCode): bool;
}
