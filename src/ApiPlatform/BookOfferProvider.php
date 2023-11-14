<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookOfferProvider implements ProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return ArrayFullPaginator|BookOffer
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

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        // TODO: do pagination via API
        $pagination = new ArrayFullPaginator($bookOffers, $page, $perPage);

        return $pagination;
    }
}
