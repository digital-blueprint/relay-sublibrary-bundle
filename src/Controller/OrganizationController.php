<?php

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Service\TUGOnlineApi;

abstract class OrganizationController
{
    protected $tugOnlineApi;

    protected $almaApi;

    public function __construct(TUGOnlineApi $tugOnlineApi, AlmaApi $almaApi)
    {
        $this->tugOnlineApi = $tugOnlineApi;
        $this->almaApi = $almaApi;
        $almaApi->checkPermissions();
    }
}
