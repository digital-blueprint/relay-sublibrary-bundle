<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaApi;
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
     * @param BookOffer $data
     *
     * @return BookOffer
     */
    public function persist($data)
    {
        $bookOffer = $data;
        $api = $this->api;
        $api->checkPermissions();
        $api->updateBookOffer($bookOffer);

        return $bookOffer;
    }

    /**
     * @param BookOffer $data
     */
    public function remove($data)
    {
    }
}
