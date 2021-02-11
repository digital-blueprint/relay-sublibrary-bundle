<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Service\PersonProviderInterface;

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
}
