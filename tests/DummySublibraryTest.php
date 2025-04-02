<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use PHPUnit\Framework\TestCase;

class DummySublibraryTest extends TestCase
{
    public function testBasic(): void
    {
        $provider = new DummySublibraryProvider();
        $library = $provider->getSublibrary('1234');
        $this->assertSame('1234', $library->getIdentifier());
        $this->assertSame('1234', $library->getCode());
        $this->assertSame('1234', $library->getName());
    }
}
