<?php

declare(strict_types=1);

use Dbp\Relay\SublibraryBundle\Service\DummySublibraryProvider;

class DummySublibraryTest extends PHPUnit\Framework\TestCase
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
