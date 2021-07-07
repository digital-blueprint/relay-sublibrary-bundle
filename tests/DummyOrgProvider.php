<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests;

use DBP\API\BaseBundle\API\OrganizationProviderInterface;
use DBP\API\BaseBundle\Entity\Organization;
use DBP\API\BaseBundle\Entity\Person;

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
