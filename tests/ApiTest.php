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
            $path = str_replace('.{_format}', '', $path);
            foreach ($route->getMethods() as $method) {
                $client = self::createClient();
                $response = $client->request($method, $path);
                $this->assertContains($response->getStatusCode(), [401, 404, 403], $path);
            }
        }
    }
}
