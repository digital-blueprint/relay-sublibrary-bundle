<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Helpers;

use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;
use DBP\API\CoreBundle\Service\OrganizationProviderInterface;

class Tools
{
    public static function getOrganizationLibraryID(Organization $organization): ?string
    {
        // XXX: we shouldn't depend on the format of the alternateName for the ID
        // but at least we have it in one place here..
        return $organization->getAlternateName();
    }

    /**
     * @return string[]
     */
    public static function getLibraryIDs(OrganizationProviderInterface $orgProvider, Person $person): array
    {
        $orgs = $orgProvider->getOrganizationsByPerson($person, 'library-manager', 'en');
        $institutes = [];
        foreach ($orgs as $org) {
            $id = self::getOrganizationLibraryID($org);
            if ($id !== null) {
                $institutes[] = $id;
            }
        }

        return $institutes;
    }

    public static function hasOrganizationPermissions(OrganizationProviderInterface $orgProvider, Person $person, Organization $organization)
    {
        $institutes = self::getLibraryIDs($orgProvider, $person);
        $institute = self::getOrganizationLibraryID($organization);

        return in_array($institute, $institutes, true);
    }

    public static function hasBookOfferPermissions(OrganizationProviderInterface $orgProvider, Person $person, BookOffer $bookOffer): bool
    {
        $institutes = self::getLibraryIDs($orgProvider, $person);
        $bookOfferLibrary = $bookOffer->getLibrary();

        return in_array($bookOfferLibrary, $institutes, true);
    }

    /**
     * @param BookLoan[] $bookLoans
     *
     * @return BookLoan[]
     */
    public static function filterBookLoans(OrganizationProviderInterface $orgProvider, Person $person, array $bookLoans): array
    {
        $institutes = self::getLibraryIDs($orgProvider, $person);
        $filtered = [];
        foreach ($bookLoans as $bookLoan) {
            $bookOfferLibrary = $bookLoan->getObject()->getLibrary();
            if (in_array($bookOfferLibrary, $institutes, true)) {
                $filtered[] = $bookLoan;
            }
        }

        return $filtered;
    }
}
