<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\CoreBundle\TestUtils\AbstractApiTest;
use Symfony\Component\HttpFoundation\Response;

class Test extends AbstractApiTest
{
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
