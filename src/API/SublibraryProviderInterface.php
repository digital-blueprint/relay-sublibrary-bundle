<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\API;

use DBP\API\AlmaBundle\Entity\Sublibrary;
use Dbp\Relay\BasePersonBundle\Entity\Person;

interface SublibraryProviderInterface
{
    public function getSublibrary(string $identifier, array $options = []): ?Sublibrary;

    /**
     * @return Sublibrary[]
     */
    public function getSublibrariesByPerson(Person $person, array $options = []): array;

    public function hasPersonSublibraryPermissions(Person $person, string $sublibraryIdentifier);
}
