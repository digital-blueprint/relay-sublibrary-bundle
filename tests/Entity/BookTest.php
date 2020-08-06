<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests\Entity;

use DBP\API\AlmaBundle\Entity\Book;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testBasics()
    {
        $book = new Book();
        $book->setIdentifier('foo');
        $this->assertEquals('foo', $book->getIdentifier());
    }
}
