<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookOrderProvider implements ProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    /** @var AlmaApi */
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return ArrayFullPaginator|BookOrder
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            $id = $uriVariables['identifier'];
            assert(is_string($id));

            return $this->api->getBookOrder($id);
        }

        $filters = $context['filters'] ?? [];

        $collection = $this->api->getBookOrders($filters);

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($context['filters']['page'])) {
            $page = (int) $context['filters']['page'];
        }

        if (isset($context['filters']['perPage'])) {
            $perPage = (int) $context['filters']['perPage'];
        }

        return new ArrayFullPaginator($collection, $page, $perPage);
    }
}
