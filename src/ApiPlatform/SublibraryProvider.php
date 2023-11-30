<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Authorization\AuthorizationService;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Response;

final class SublibraryProvider implements ProviderInterface
{
    private const PERSON_ID_FILTER_NAME = 'libraryManager';

    /** @var AlmaApi */
    private $api;

    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    /** @var AuthorizationService */
    private $authorizationService;

    public function __construct(AlmaApi $api, SublibraryProviderInterface $libraryProvider, AuthorizationService $authorizationService)
    {
        $this->api = $api;
        $this->libraryProvider = $libraryProvider;
        $this->authorizationService = $authorizationService;
    }

    /**
     * @return WholeResultPaginator|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            return null;
        }

        $filters = $context['filters'] ?? [];
        $personId = $filters[self::PERSON_ID_FILTER_NAME] ?? null;

        if (empty($personId)) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, "parameter '".self::PERSON_ID_FILTER_NAME."' is mandatory.");
        }

        $currentPerson = $this->api->getCurrentPerson();

        // users are only allowed to fetch this for themselves
        if ($personId !== $currentPerson->getIdentifier()) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'Only allowed with person ID of currently logged-in person.');
        }

        $options = [];
        $options['lang'] = $filters['lang'] ?? 'de';

        $sublibraries = [];
        try {
            foreach ($this->authorizationService->getSublibraryIdsForCurrentUser() as $sublibraryId) {
                $sublibrary = $this->libraryProvider->getSublibrary($sublibraryId, $options);
                if ($sublibrary !== null) {
                    $lib = new Sublibrary();
                    $lib->setIdentifier($sublibrary->getIdentifier());
                    $lib->setName($sublibrary->getName());
                    $lib->setCode($sublibrary->getCode());
                    $sublibraries[] = $lib;
                }
            }
        } catch (\Exception $exc) {
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, $exc->getMessage());
        }

        return new WholeResultPaginator($sublibraries,
            Pagination::getCurrentPageNumber($filters),
            Pagination::getMaxNumItemsPerPage($filters));
    }
}
