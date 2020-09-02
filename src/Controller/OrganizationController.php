<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Helpers\Tools;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Entity\Organization;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class OrganizationController
{
    protected $personProvider;

    protected $almaApi;

    public function __construct(PersonProviderInterface $personProvider, AlmaApi $almaApi)
    {
        $this->personProvider = $personProvider;
        $this->almaApi = $almaApi;
        $almaApi->checkPermissions();
    }

    /**
     * Checks if the current user has permissions to an organization.
     *
     * @throws AccessDeniedHttpException
     */
    public function checkOrganizationPermissions(Organization $organization)
    {
        $person = $this->personProvider->getCurrentPerson();
        Tools::checkOrganizationPermissions($person, $organization);
    }
}
