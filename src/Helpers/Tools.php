<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Helpers;

use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Tools
{
    /**
     * Returns the institutes for a group (e.g. "F_BIB").
     */
    public static function getInstitutesForGroup(Person $person, string $group): array
    {
        $functions = $person->getFunctions();
        $group = preg_quote($group);
        $results = [];
        $re = "/^$group:F:(\d+):[\d_]+$/i";

        foreach ($functions as $function) {
            if (preg_match($re, $function, $matches)) {
                $results[] = 'F'.$matches[1];
            }
        }

        return $results;
    }

    /**
     * Checks if the user has permissions to an organization.
     *
     * @throws AccessDeniedHttpException
     */
    public static function checkOrganizationPermissions(Person $person, Organization $organization)
    {
        $institutes = self::getInstitutesForGroup($person, 'F_BIB');
        $institute = $organization->getAlternateName();

        // check if current user has F_BIB permissions to the institute of the book offer
        if (!in_array($institute, $institutes, true)) {
            // throw an exception if we want to
            throw new AccessDeniedHttpException(sprintf("Person '%s' is not allowed to work with library '%s'!", $person->getIdentifier(), $institute));
        }
    }
}
