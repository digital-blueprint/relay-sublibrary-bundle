<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;

class Test extends ApiTestCase
{
    /** @var Client */
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testIndex()
    {
        $response = $this->client->request('GET', '/sublibrary/books');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $response = $this->client->request('GET', '/sublibrary/books', ['headers' => ['HTTP_ACCEPT' => 'application/ld+json']]);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }
}
