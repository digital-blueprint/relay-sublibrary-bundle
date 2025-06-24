<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Locale\Locale;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Dbp\Relay\SublibraryBundle\Authorization\AuthorizationService;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Dbp\Relay\SublibraryBundle\Sublibrary\SublibraryProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProviderInterface<Sublibrary>
 */
final class SublibraryProvider implements ProviderInterface
{
    private const PERSON_ID_FILTER_NAME = 'libraryManager';

    /** @var AlmaApi */
    private $api;

    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    /** @var AuthorizationService */
    private $authorizationService;

    public function __construct(AlmaApi $api, SublibraryProviderInterface $libraryProvider, AuthorizationService $authorizationService, private Locale $locale)
    {
        $this->api = $api;
        $this->libraryProvider = $libraryProvider;
        $this->authorizationService = $authorizationService;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $this->api->checkPermissions();

        $filters = $context['filters'] ?? [];
        $options = [];

        // Read the query param for backwards compat
        $this->locale->setCurrentRequestLocaleFromQuery();
        $options['lang'] = $this->locale->getCurrentPrimaryLanguage();

        if (!$operation instanceof CollectionOperationInterface) {
            $sublibraryId = $uriVariables['identifier'];
            $allowedIds = $this->authorizationService->getSublibraryIdsForCurrentUser();
            if (in_array($sublibraryId, $allowedIds, true)) {
                $sublibrary = $this->libraryProvider->getSublibrary($sublibraryId, $options);
                if ($sublibrary !== null) {
                    $lib = new Sublibrary();
                    $lib->setIdentifier($sublibrary->getIdentifier());
                    $lib->setName($sublibrary->getName());
                    $lib->setCode($sublibrary->getCode());

                    return $lib;
                } else {
                    return null;
                }
            }

            return null;
        }

        $currentPerson = $this->api->getCurrentPerson(false);
        $currentPersonId = $currentPerson->getIdentifier();
        $personId = $filters[self::PERSON_ID_FILTER_NAME] ?? $currentPersonId;

        // users are only allowed to fetch this for themselves
        if ($personId === null || $personId !== $currentPersonId) {
            throw new ApiError(Response::HTTP_FORBIDDEN, 'Only allowed with person ID of currently logged-in person.');
        }

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
