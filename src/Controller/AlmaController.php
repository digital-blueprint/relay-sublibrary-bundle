<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Controller;

use Dbp\Relay\BasePersonBundle\API\PersonProviderInterface;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotStoredException;
use Dbp\Relay\SublibraryBundle\Helpers\Tools;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class AlmaController extends AbstractController
{
    /** @var AlmaApi */
    protected $api;

    /** @var PersonProviderInterface */
    protected $personProvider;

    /** @var SublibraryProviderInterface */
    protected $libraryProvider;

    public function __construct(AlmaApi $api, PersonProviderInterface $personProvider, SublibraryProviderInterface $libraryProvider)
    {
        $this->api = $api;
        $this->personProvider = $personProvider;
        $this->libraryProvider = $libraryProvider;
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
