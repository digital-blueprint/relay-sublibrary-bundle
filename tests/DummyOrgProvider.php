<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests;

use Dbp\Relay\BaseOrganizationBundle\API\OrganizationProviderInterface;
use Dbp\Relay\BaseOrganizationBundle\Entity\Organization;
use Dbp\Relay\BasePersonBundle\Entity\Person;

class DummyOrgProvider implements OrganizationProviderInterface
{
    public function getOrganizationById(string $identifier, string $lang): Organization
    {
        return new Organization();
    }

    public function getOrganizationsByPerson(Person $person, string $context, string $lang): array
    {
        return [];
    }

    public function getOrganizations(string $lang): array
    {
        return [];
    }
}
