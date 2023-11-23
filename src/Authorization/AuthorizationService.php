<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Authorization;

use Dbp\Relay\CoreBundle\Authorization\AbstractAuthorizationService;
use Dbp\Relay\SublibraryBundle\DependencyInjection\Configuration;

class AuthorizationService extends AbstractAuthorizationService
{
    public function isLibraryManager(): bool
    {
        return $this->isGranted(Configuration::ROLE_LIBRARY_MANAGER);
    }

    public function isLibraryManagerById(string $libraryId): bool
    {
        return in_array($libraryId, $this->getSublibraryIdsForCurrentUser(), true);
    }

    public function isLibraryManagerByAlmaId(string $almaLibraryId): bool
    {
        return in_array($almaLibraryId, $this->getAlmaLibraryIdsForCurrentUser(), true);
    }

    /**
     * Returns all sublibrary IDs the current user has library manager permissions in.
     *
     * @return string[]
     */
    public function getSublibraryIdsForCurrentUser(): array
    {
        return $this->getAttribute(Configuration::SUBLIBRARY_IDS, []) ?? [];
    }

    /**
     * Returns all ALMA library codes the current user has library manager permissions in.
     *
     * @return string[]
     */
    public function getAlmaLibraryIdsForCurrentUser(): array
    {
        return $this->getAttribute(Configuration::ALMA_LIBRARY_IDS, []) ?? [];
    }

    public function validateConfiguration()
    {
        $this->isLibraryManager();
        $this->getSublibraryIdsForCurrentUser();
        $this->getAlmaLibraryIdsForCurrentUser();
    }
}
