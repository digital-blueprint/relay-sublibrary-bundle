<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Tests;

use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaUrlApi;
use DBP\API\AlmaBundle\Service\InvalidIdentifierException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlmaApiUrlTest extends WebTestCase
{
    /* @var AlmaUrlApi */
    private $urls;

    protected function setUp(): void
    {
        $this->urls = new AlmaUrlApi();
    }

    public function test_getBookUrl()
    {
        $this->assertEquals('bibs/foob%3Far', $this->urls->getBookUrl('foob?ar'));
    }

    public function test_getBookLoanPostUrl()
    {
        $this->assertEquals(
            'bibs/foo/holdings/b%3Fr/items/baz/loans?user_id=bar',
            $this->urls->getBookLoanPostUrl('foo-b?r-baz', 'bar'));
    }

    public function test_getBookOfferUrl()
    {
        $this->assertEquals(
            'bibs/%C3%B6%C3%A4%2F/holdings/%C3%B6%23/items/a%3B',
            $this->urls->getBookOfferUrl('öä/-ö#-a;'));

        $this->expectException(InvalidIdentifierException::class);
        $this->urls->getBookOfferUrl('foo');
    }

    public function test_getBookLoanUrl()
    {
        $this->assertEquals(
            'bibs/%C3%B6%C3%A4%2F/holdings/%C3%B6%23/items/a%3B/loans/%20',
            $this->urls->getBookLoanUrl('öä/-ö#-a;- '));

        $this->expectException(InvalidIdentifierException::class);
        $this->urls->getBookLoanUrl('foo');
    }

    public function test_getReturnBookOfferUrl()
    {
        $this->assertEquals(
            'bibs/foo/holdings/ba%3Fr/items/baz?op=scan&library=&circ_desk=DEFAULT_CIRC_DESK',
            $this->urls->getReturnBookOfferUrl('foo-ba?r-baz'));
        $this->assertEquals(
            'bibs/foo/holdings/ba%3Fr/items/baz?op=scan&library=lib&circ_desk=DEFAULT_CIRC_DESK',
            $this->urls->getReturnBookOfferUrl('foo-ba?r-baz', 'lib'));
    }

    public function test_getBookOfferLoansUrl()
    {
        $this->assertEquals(
            'bibs/fo/holdings/ba%3Fr/items/baz/loans',
            $this->urls->getBookOfferLoansUrl('fo-ba?r-baz'));
    }

    public function test_getLoansByUserIdUrl()
    {
        $this->assertEquals(
            'users/bla%3F/loans?limit=100&offset=0',
            $this->urls->getLoansByUserIdUrl('bla?'));
        $this->assertEquals(
            'users/bla%3F/loans?limit=20&offset=0',
            $this->urls->getLoansByUserIdUrl('bla?', 20));
    }

    public function test_getBarcodeBookOfferUrl()
    {
        $this->assertEquals(
            'items?item_barcode=bla%3F',
            $this->urls->getBarcodeBookOfferUrl('bla?'));
    }

    public function test_getBookOfferLocationsIdentifierUrl()
    {
        $offer = new BookOffer();
        $offer->setIdentifier('foo-ba?r-baz');
        $this->assertEquals(
            'bibs/foo/holdings/ba%3Fr/items?order_by=chron_i&limit=100',
            $this->urls->getBookOfferLocationsIdentifierUrl($offer));

        $offer->setIdentifier('bla');
        $this->expectException(InvalidIdentifierException::class);
        $this->urls->getBookOfferLocationsIdentifierUrl($offer);
    }
}
