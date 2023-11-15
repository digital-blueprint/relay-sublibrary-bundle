<?php

declare(strict_types=1);
/**
 * LDAP wrapper service.
 */

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\Tools as CoreTools;
use LdapRecord\Auth\BindException;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\Entry;
use LdapRecord\Models\Model;
use LdapRecord\Query\Builder;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LDAPApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    // singleton to cache fetched users by alma user id
    public static $USERS_BY_ALMA_USER_ID = [];

    private $cachePool;

    private $cacheTTL;

    private $providerConfig;

    private $identifierAttributeName;

    private $almaUserIdAttributeName;

    public function __construct()
    {
        $this->cacheTTL = 0;
        $this->logger = new NullLogger();
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
        $connection = $this->getConnection();
        $builder = $this->getCachedBuilder($connection);
        $builder->first();
    }

    public function setLDAPCache(?CacheItemPoolInterface $cachePool, int $ttl)
    {
        $this->cachePool = $cachePool;
        $this->cacheTTL = $ttl;
    }

    private function getConnection(): Connection
    {
        Container::getInstance()->manager()->setLogger($this->logger);
        $connection = new Connection($this->providerConfig);
        if ($this->cachePool !== null) {
            $connection->setCache(new Psr16Cache($this->cachePool));
        }
        $connection->connect();

        return $connection;
    }

    private function getCachedBuilder(Connection $connection): Builder
    {
        $until = (new \DateTimeImmutable())->add(new \DateInterval('PT'.$this->cacheTTL.'S'));

        return $connection->query()->cache($until);
    }

    private function getPersonUserItemByAlmaUserId(string $almaUserId): ?Model
    {
        try {
            // if we already have fetched the user by alma user id in this request we will use the cached version
            if (array_key_exists($almaUserId, self::$USERS_BY_ALMA_USER_ID)) {
                $user = self::$USERS_BY_ALMA_USER_ID[$almaUserId];
            } else {
                $connection = $this->getConnection();
                $builder = $this->getCachedBuilder($connection);

                /** @var Entry $user */
                $user = $builder->model(new Entry())->where('objectClass', '=', 'person')
                    ->whereEquals($this->almaUserIdAttributeName, $almaUserId)
                    ->first();

                self::$USERS_BY_ALMA_USER_ID[$almaUserId] = $user;
            }

            if ($user === null) {
                throw new NotFoundHttpException(sprintf("Person with alma user id '%s' could not be found!", $almaUserId));
            }

            return $user;
        } catch (BindException $e) {
            // There was an issue binding / connecting to the server.
            throw new ApiError(Response::HTTP_BAD_GATEWAY, sprintf("Person with alma user id '%s' could not be loaded! Message: %s", $almaUserId, CoreTools::filterErrorMessage($e->getMessage())));
        }
    }

    public function getPersonIdByAlmaUserId(string $almaUserId): string
    {
        $user = $this->getPersonUserItemByAlmaUserId($almaUserId);

        return $user->getFirstAttribute($this->identifierAttributeName);
    }
}
