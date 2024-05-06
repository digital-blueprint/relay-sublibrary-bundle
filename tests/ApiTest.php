<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class ApiTest extends ApiTestCase
{
    public function testNotAuth()
    {
        $client = self::createClient();
        /* @var $router \Symfony\Component\Routing\Router */
        $router = $client->getContainer()->get('router');
        $collection = $router->getRouteCollection();
        foreach ($collection as $key => $route) {
            $path = $route->getPath();
            $path = str_replace('.{_format}', '', $path);
            foreach ($route->getMethods() as $method) {
                $client = self::createClient();
                $contentType = ($method === 'PATCH') ? 'application/merge-patch+json' : 'application/ld+json';
                $response = $client->request($method, $path.'?sublibrary=1234', ['headers' => ['Content-Type' => $contentType]]);
                $this->assertContains($response->getStatusCode(), [401, 404], $path);
            }
        }
    }
}
