<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

/**
 * @psalm-suppress MissingTemplateParam
 */
final class BookOfferProcessor implements ProcessorInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * Updates an item in Alma.
     *
     * @param mixed $data
     *
     * @return BookOffer
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        assert($data instanceof BookOffer);
        $bookOffer = $data;
        $api = $this->api;
        $api->checkPermissions();

        if ($operation instanceof Patch) {
            $api->updateBookOffer($bookOffer);
        }

        return $bookOffer;
    }
}
