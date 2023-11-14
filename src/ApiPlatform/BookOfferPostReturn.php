<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookOfferPostReturn extends AbstractController
{
    /** @var AlmaApi */
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(string $identifier): BookOffer
    {
        $this->api->checkPermissions();

        $bookOffer = $this->api->getBookOffer($identifier);
        $this->api->returnBookOffer($bookOffer);

        // returnBookOffer doesn't return anything so we just return the book offer we got instead
        return $bookOffer;
    }
}
