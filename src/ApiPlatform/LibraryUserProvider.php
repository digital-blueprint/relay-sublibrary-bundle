<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\PartialPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

/**
 * @implements ProviderInterface<LibraryUser>
 */
final class LibraryUserProvider implements ProviderInterface
{
    public function __construct(private AlmaApi $api)
    {
    }

    /**
     * @return PartialPaginator<LibraryUser>|LibraryUser
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $this->api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            $id = $uriVariables['identifier'];
            assert(is_string($id));

            return $this->api->getLibraryUser($id);
        }

        $filters = $context['filters'] ?? [];
        $users = $this->api->getLibraryUsers($filters);
        $maxNumItemsPerPage = min(100, Pagination::getMaxNumItemsPerPage($filters));

        /** @var PartialPaginator<LibraryUser> $paginator */
        $paginator = new PartialPaginator($users,
            Pagination::getCurrentPageNumber($filters),
            $maxNumItemsPerPage);

        return $paginator;
    }
}
