<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Dbp\Relay\SublibraryBundle\Helpers\ItemNotStoredException;
use Dbp\Relay\SublibraryBundle\Helpers\Tools;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AlmaController extends AbstractController
{
    /** @var AlmaApi */
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
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
