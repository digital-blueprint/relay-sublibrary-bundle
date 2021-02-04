<?php


namespace DBP\API\AlmaBundle\Tests;


use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;

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
}