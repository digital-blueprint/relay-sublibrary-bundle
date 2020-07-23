<?php


namespace DBP\API\AlmaBundle\Tests\Entity;


use DBP\API\AlmaBundle\Entity\Book;
use DBP\API\AlmaBundle\Entity\BookOffer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookOfferTest extends WebTestCase
{
    public function testBasics() {
        $offer = new BookOffer();
        $offer->setBook(new Book());
        $name = $offer->getName();
        $this->assertNull($name);
    }
}