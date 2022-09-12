<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Service;

use Dbp\Relay\CoreBundle\HealthCheck\CheckInterface;
use Dbp\Relay\CoreBundle\HealthCheck\CheckOptions;
use Dbp\Relay\CoreBundle\HealthCheck\CheckResult;

class HealthCheck implements CheckInterface
{
    private $api;
    private $ldap;

    public function __construct(AlmaApi $api, LDAPApi $ldap)
    {
        $this->api = $api;
        $this->ldap = $ldap;
    }

    public function getName(): string
    {
        return 'sublibrary';
    }

    private function checkMethod(string $description, callable $func): CheckResult
    {
        $result = new CheckResult($description);
        try {
            $func();
        } catch (\Throwable $e) {
            $result->set(CheckResult::STATUS_FAILURE, $e->getMessage(), ['exception' => $e]);

            return $result;
        }
        $result->set(CheckResult::STATUS_SUCCESS);

        return $result;
    }

    public function check(CheckOptions $options): array
    {
        $results = [];
        $results[] = $this->checkMethod('Check if we can connect to the Alma "bibs" API', [$this->api, 'checkConnection']);
        $results[] = $this->checkMethod('Check if we can connect to the Alma "analytics" API', [$this->api, 'checkConnectionAnalytics']);
        $results[] = $this->checkMethod('Check if we can connect to the LDAP server', [$this->ldap, 'checkConnection']);

        return $results;
    }
}
