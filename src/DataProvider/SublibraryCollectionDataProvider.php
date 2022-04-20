<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\API\SublibraryProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\Sublibrary;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Response;

final class SublibraryCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    private const PERSON_ID_FILTER_NAME = 'libraryManager';

    /** @var AlmaApi */
    private $api;

    /** @var SublibraryProviderInterface */
    private $libraryProvider;

    public function __construct(AlmaApi $api, SublibraryProviderInterface $libraryProvider)
    {
        $this->api = $api;
        $this->libraryProvider = $libraryProvider;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Sublibrary::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): ArrayFullPaginator
    {
        $this->api->checkPermissions();

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
            foreach ($this->libraryProvider->getSublibraryIdsByLibraryManager($currentPerson) as $sublibraryId) {
                $sublibrary = $this->libraryProvider->getSublibrary($sublibraryId, $options);
                if ($sublibrary !== null) {
                    $sublibraries[] = $sublibrary;
                }
            }
        } catch (\Exception $exc) {
            throw new ApiError(Response::HTTP_INTERNAL_SERVER_ERROR, $exc->getMessage());
        }

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($filters['page'])) {
            $page = (int) $filters['page'];
        }

        if (isset($filters['perPage'])) {
            $perPage = (int) $filters['perPage'];
        }

        return new ArrayFullPaginator($sublibraries, $page, $perPage);
    }
}
