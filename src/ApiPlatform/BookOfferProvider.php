<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookOfferProvider implements ProviderInterface
{
    /** @var AlmaApi */
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return WholeResultPaginator|BookOffer
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $api = $this->api;
        $api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            $id = $uriVariables['identifier'];
            assert(is_string($id));

            $bookOffer = $api->getBookOffer($id);
            $this->api->checkCurrentPersonBookOfferPermissions($bookOffer);

            return $bookOffer;
        }

        $filters = $context['filters'] ?? [];
        $bookOffers = $api->getBookOffers($filters);

        // TODO: do pagination via API
        return new WholeResultPaginator($bookOffers->toArray(),
            Pagination::getCurrentPageNumber($filters),
            Pagination::getMaxNumItemsPerPage($filters));
    }
}
