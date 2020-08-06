<?php

namespace DBP\API\AlmaBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaApi;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
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
     * @param BookOffer $bookOffer
     *
     * @return BookOffer
     *
     * @throws ItemNotStoredException
     * @throws ItemNotLoadedException
     */
    public function persist($bookOffer)
    {
        $api = $this->api;
        $api->checkPermissions();
        $api->updateBookOffer($bookOffer);

        return $bookOffer;
    }

    /**
     * @param BookOffer $bookOffer
     */
    public function remove($bookOffer)
    {
    }
}
