<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\CoreBundle\TestUtils\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class Test extends ApiTestCase
{
    public function setUp(): void
    {
        $this->createTestClient();
    }

    public function testIndex()
    {
        $response = $this->testClient->request('GET', '/sublibrary/books', token: null);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testJSONLD()
    {
        $response = $this->testClient->request('GET', '/sublibrary/books', [
            'headers' => ['HTTP_ACCEPT' => 'application/ld+json'],
        ], token: null);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertJson($response->getContent(false));
    }
}
