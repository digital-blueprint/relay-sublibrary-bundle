<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Service\PersonProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class OrganizationController extends AbstractController
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
