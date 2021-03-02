<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

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
            foreach ($route->getMethods() as $method) {
                $client = self::createClient();
                $response = $client->request($method, $path);
                $this->assertEquals(401, $response->getStatusCode(), $path);
            }
        }
    }
}
