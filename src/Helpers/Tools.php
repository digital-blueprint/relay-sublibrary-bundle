<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Helpers;

use Dbp\Relay\SublibraryBundle\Entity\BookLoan;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\BaseOrganizationBundle\API\OrganizationProviderInterface;
use Dbp\Relay\BaseOrganizationBundle\Entity\Organization;
use Dbp\Relay\BasePersonBundle\Entity\Person;

class Tools
{
    /**
     * Like json_decode but throws on invalid json data.
     *
     * @throws \JsonException
     *
     * @return mixed
     */
    public static function decodeJSON(string $json, bool $assoc = false)
    {
        $result = json_decode($json, $assoc);
        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            throw new \JsonException(sprintf('%s: "%s"', json_last_error_msg(), print_r($json, true)));
        }

        return $result;
    }

    public static function filterErrorMessage(string $message): string
    {
        // hide token parameters
        return preg_replace('/([&?]token=)[\w\d-]+/i', '${1}hidden', $message);
    }

    public static function getOrganizationLibraryID(Organization $organization): ?string
    {
        return explode('-', $organization->getIdentifier())[1];
    }

    /**
     * @return string[]
     */
    public static function getLibraryIDs(OrganizationProviderInterface $orgProvider, Person $person): array
    {
        $orgs = $orgProvider->getOrganizationsByPerson($person, 'library-manager', ['lang' => 'en']);
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

        return $institute !== null && in_array($institute, $institutes, true);
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
