<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\API;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;

interface SublibraryProviderInterface
{
    public function getSublibrary(string $identifier, array $options = []): ?Sublibrary;

    /**
     * @return Sublibrary[]
     */
    public function getSublibrariesByPerson(Person $person, array $options = []): array;

    public function hasPersonSublibraryPermissions(Person $person, string $sublibraryIdentifier);
}
