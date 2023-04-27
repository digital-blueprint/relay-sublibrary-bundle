<?php

declare(strict_types=1);
/**
 * LDAP wrapper service.
 *
 * @see https://github.com/Adldap2/Adldap2
 */

namespace Dbp\Relay\SublibraryBundle\Service;

use Adldap\Adldap;
use Adldap\Connections\Provider;
use Adldap\Connections\ProviderInterface;
use Adldap\Models\User;
use Adldap\Query\Builder;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\CoreBundle\API\UserSessionInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools as CoreTools;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class LDAPApi implements LoggerAwareInterface, ServiceSubscriberInterface
{
    use LoggerAwareTrait;

    // singleton to cache fetched users by alma user id
    public static $USERS_BY_ALMA_USER_ID = [];

    private $PAGESIZE = 50;

    /**
     * @var Adldap
     */
    private $ad;

    private $cachePool;

    private $personCache;

    private $cacheTTL;

    /**
     * @var Person|null
     */
    private $currentPerson;

    private $providerConfig;

    private $identifierAttributeName;

    private $almaUserIdAttributeName;

    public function __construct()
    {
        $this->ad = new Adldap();
        $this->cacheTTL = 0;
        $this->currentPerson = null;
    }

    public function setConfig(array $config)
    {
        $this->identifierAttributeName = $config['attributes']['identifier'] ?? 'cn';
        $this->almaUserIdAttributeName = $config['attributes']['alma_user_id'] ?? 'CO-ALMA-PATRON-ID';

        $this->providerConfig = [
            'hosts' => [$config['host'] ?? ''],
            'base_dn' => $config['base_dn'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
        ];

        $encryption = $config['encryption'] ?? '';
        assert(in_array($encryption, ['start_tls', 'simple_tls'], true));
        $this->providerConfig['use_tls'] = ($encryption === 'start_tls');
        $this->providerConfig['use_ssl'] = ($encryption === 'simple_tls');
        $this->providerConfig['port'] = ($encryption === 'start_tls') ? 389 : 636;
    }

    public function checkConnection()
    {
        $provider = $this->getProvider();
        $builder = $this->getCachedBuilder($provider);
        $builder->first();
    }

    public function setLDAPCache(?CacheItemPoolInterface $cachePool, int $ttl)
    {
        $this->cachePool = $cachePool;
        $this->cacheTTL = $ttl;
    }

    public function setPersonCache(?CacheItemPoolInterface $cachePool)
    {
        $this->personCache = $cachePool;
    }

    private function getProvider(): ProviderInterface
    {
        if ($this->logger !== null) {
            Adldap::setLogger($this->logger);
        }
        $ad = new Adldap();
        $ad->addProvider($this->providerConfig);
        $provider = $ad->connect();
        assert($provider instanceof Provider);
        if ($this->cachePool !== null) {
            $provider->setCache(new Psr16Cache($this->cachePool));
        }

        return $provider;
    }

    private function getCachedBuilder(ProviderInterface $provider): Builder
    {
        // FIXME: https://github.com/Adldap2/Adldap2/issues/786
        // return $provider->search()->cache($until=$this->cacheTTL);
        // We depend on the default TTL of the cache for now...

        /**
         * @var Builder $builder
         */
        $builder = $provider->search()->cache();

        return $builder;
    }

    private function getPersonUserItemByAlmaUserId(string $almaUserId): ?User
    {
        try {
            $provider = $this->getProvider();
            $builder = $this->getCachedBuilder($provider);

            // if we already have fetched the user by alma user id in this request we will use the cached version
            if (array_key_exists($almaUserId, self::$USERS_BY_ALMA_USER_ID)) {
                $user = self::$USERS_BY_ALMA_USER_ID[$almaUserId];
            } else {
                /** @var User $user */
                $user = $builder
                    ->where('objectClass', '=', $provider->getSchema()->person())
                    ->whereEquals($this->almaUserIdAttributeName, $almaUserId)
                    ->first();

                self::$USERS_BY_ALMA_USER_ID[$almaUserId] = $user;
            }

            if ($user === null) {
                throw new NotFoundHttpException(sprintf("Person with alma user id '%s' could not be found!", $almaUserId));
            }

            return $user;
        } catch (\Adldap\Auth\BindException $e) {
            // There was an issue binding / connecting to the server.
            throw new ApiError(Response::HTTP_BAD_GATEWAY, sprintf("Person with alma user id '%s' could not be loaded! Message: %s", $almaUserId, CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    public function getPersonIdByAlmaUserId(string $almaUserId): string
    {
        $user = $this->getPersonUserItemByAlmaUserId($almaUserId);

        return $user->getFirstAttribute($this->identifierAttributeName);
    }

    public static function getSubscribedServices(): array
    {
        return [
            UserSessionInterface::class,
        ];
    }
}
