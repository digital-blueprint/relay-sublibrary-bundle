<?php

namespace DBP\API\AlmaBundle\Controller;

use App\Exception\ItemNotStoredException;
use App\Helpers\JsonException;
use DBP\API\CoreBundle\Helpers\Tools;
use DBP\API\AlmaBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Request;

abstract class AlmaController {
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
        $api->checkPermissions();
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ItemNotStoredException
     */
    protected function decodeRequest(Request $request)
    {
        $content = (string)$request->getContent();
        try {
            return Tools::decodeJSON($content, true);
        } catch (JsonException $e) {
            throw new ItemNotStoredException(sprintf("Invalid json: %s", Tools::filterErrorMessage($e->getMessage())));
        }
    }
}
