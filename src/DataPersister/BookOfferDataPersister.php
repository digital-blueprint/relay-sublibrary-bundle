<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\RequestStack;

final class BookOfferDataPersister implements DataPersisterInterface
{
    private $api;

    private $requestStack;

    public function __construct(AlmaApi $api, RequestStack $requestStack)
    {
        $this->api = $api;
        $this->requestStack = $requestStack;
    }

    public function supports($data): bool
    {
        return $data instanceof BookOffer;
    }

    /**
     * Updates an item in Alma.
     *
     * @param mixed $data
     *
     * @return BookOffer
     */
    public function persist($data)
    {
        assert($data instanceof BookOffer);
        $bookOffer = $data;
        $api = $this->api;
        $api->checkPermissions();
        $api->updateBookOffer($bookOffer);

        return $bookOffer;
    }

    /**
     * @param mixed $data
     */
    public function remove($data)
    {
    }
}
