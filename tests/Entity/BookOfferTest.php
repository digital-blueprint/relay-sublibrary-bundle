<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests\Entity;

use Dbp\Relay\SublibraryBundle\Entity\Book;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookOfferTest extends WebTestCase
{
    public function testBasics()
    {
        $offer = new BookOffer();
        $offer->setBook(new Book());
        $name = $offer->getName();
        $this->assertNull($name);
    }
}
