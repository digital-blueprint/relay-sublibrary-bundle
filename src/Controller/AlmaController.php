<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Helpers\ItemNotStoredException;
use DBP\API\AlmaBundle\Helpers\Tools;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\BaseBundle\API\OrganizationProviderInterface;
use DBP\API\BaseBundle\API\PersonProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AlmaController extends AbstractController
{
    protected $api;
    protected $personProvider;
    protected $orgProvider;

    public function __construct(AlmaApi $api, PersonProviderInterface $personProvider, OrganizationProviderInterface $orgProvider)
    {
        $this->api = $api;
        $this->personProvider = $personProvider;
        $this->orgProvider = $orgProvider;
    }

    protected function checkPermissions()
    {
        $this->api->checkPermissions();
    }

    /**
     * @return mixed
     *
     * @throws ItemNotStoredException
     */
    protected function decodeRequest(Request $request)
    {
        $content = (string) $request->getContent();
        try {
            return Tools::decodeJSON($content, true);
        } catch (\JsonException $e) {
            throw new ItemNotStoredException(sprintf('Invalid json: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }
}
