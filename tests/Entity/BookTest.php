<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests\Entity;

use Dbp\Relay\SublibraryBundle\ApiPlatform\Book;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookTest extends WebTestCase
{
    public function testBasics()
    {
        $book = new Book();
        $book->setIdentifier('foo');
        $this->assertEquals('foo', $book->getIdentifier());
    }
}
