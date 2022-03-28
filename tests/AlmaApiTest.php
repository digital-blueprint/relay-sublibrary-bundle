<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Tests;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\BasePersonBundle\TestUtils\DummyPersonProvider;
use Dbp\Relay\CoreBundle\Helpers\Tools;
use Dbp\Relay\SublibraryBundle\Entity\Book;
use Dbp\Relay\SublibraryBundle\Entity\BookOffer;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotLoadedException;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Dbp\Relay\SublibraryBundle\Service\LDAPApi;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Security;

class AlmaApiTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    /* @var AlmaApi */
    private $api;

    private const bookOfferResponse = '{"bib_data":{"mms_id":"990002338910204517","title":"Arch+ Zeitschrift für Architektur und Urbanismus","author":null,"issn":"0587-3452","isbn":null,"complete_edition":"","network_number":["oai:dnb.de/zdb/010677887","(DE-599)ZDB120544-4","(DE-600)120544-4","(Aleph)000233891TUG01","(AT-OBV)AC00377896","AC00377896"],"place_of_publication":"Aachen","date_of_publication":"1968-","publisher_const":"Arch + Verl","link":"https://api-eu.hosted.exlibrisgroup.com/almaws/v1/bibs/990002338910204517"},"holding_data":{"holding_id":"2211143720004517","call_number_type":{"value":"8","desc":"Other scheme"},"call_number":"ZII 85168","accession_number":"","copy_id":"","in_temp_location":false,"temp_library":{"value":null,"desc":null},"temp_location":{"value":null,"desc":null},"temp_call_number_type":{"value":"","desc":null},"temp_call_number":"","temp_policy":{"value":"","desc":null},"link":"https://api-eu.hosted.exlibrisgroup.com/almaws/v1/bibs/990002338910204517/holdings/2211143720004517"},"item_data":{"pid":"2311143700004517","barcode":"+F20313804","creation_date":"2019-10-06Z","modification_date":"2019-10-06Z","base_status":{"value":"1","desc":"Item in place"},"awaiting_reshelving":false,"physical_material_type":{"value":"ISSUE","desc":"Issue"},"policy":{"value":"07","desc":"Sonderleihfrist"},"provenance":{"value":"","desc":null},"po_line":"2017/2871","is_magnetic":false,"arrival_date":"2005-03-02Z","year_of_issue":"","enumeration_a":"172","enumeration_b":"","enumeration_c":"","enumeration_d":"","enumeration_e":"","enumeration_f":"","enumeration_g":"","enumeration_h":"","chronology_i":"2004","chronology_j":"","chronology_k":"","chronology_l":"","chronology_m":"","description":"37.2004,172","receiving_operator":"import","process_type":{"value":"","desc":null},"inventory_number":"2005/3060","inventory_date":"2004-12-27Z","inventory_price":"","library":{"value":"F1490","desc":"Inst.f.Architekturtechnologie"},"location":{"value":"IBIBL","desc":"Institutsbibliothek"},"alternative_call_number":"","alternative_call_number_type":{"value":"","desc":null},"storage_location_id":"","pages":"","pieces":"","public_note":"","fulfillment_note":"","internal_note_1":"","internal_note_2":"","internal_note_3":"","statistics_note_1":"","statistics_note_2":"","statistics_note_3":"","requested":false,"edition":null,"imprint":null,"language":null,"physical_condition":{"value":null,"desc":null}},"link":"https://api-eu.hosted.exlibrisgroup.com/almaws/v1/bibs/990002338910204517/holdings/2211143720004517/items/2311143700004517"}';

    protected function setUp(): void
    {
        $client = static::createClient();

        $person = new Person();
        $personProvider = new DummyPersonProvider($person);
        $orgProvider = new DummyOrgProvider();
        $ldapApi = new LDAPApi($personProvider);
        $ldapApi->setConfig([
            'encryption' => 'simple_tls',
        ]);

        $this->api = new AlmaApi(
            $personProvider,
            $orgProvider,
            new Security($client->getContainer()),
            $ldapApi
        );
        $this->api->setApiKey('secret');
        $this->api->setAnalyticsApiKey('secret');
        $this->mockResponses([]);
    }

    private function mockResponses(array $responses)
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $this->api->setClientHandler($stack);
    }

    public function testGetBookOfferError()
    {
        $data = '{"web_service_result":{"errorsExist":true,"errorList":{"error":{"errorCode":"NOT_FOUND","errorMessage":"","trackingId":"unknown"}}}}';

        $this->mockResponses([
            new Response(404, ['Content-Type' => 'application/json;charset=UTF-8'], $data),
        ]);

        try {
            $this->api->getBookOffer('foo-bar-baz');
        } catch (ItemNotLoadedException $e) {
            $this->assertStringContainsString('foo-bar-baz', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testGetBookOfferErrorNoResponse()
    {
        $this->mockResponses([
            new RequestException('myerror', new Request('GET', 'some-dummy-url')),
        ]);

        try {
            $this->api->getBookOffer('foo-bar-baz');
        } catch (ItemNotLoadedException $e) {
            $this->assertStringContainsString('myerror', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testGetBookOffer()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json;charset=UTF-8'], self::bookOfferResponse),
        ]);

        $id = '990002338910204517-2211143720004517-2311143700004517';
        $offer = $this->api->getBookOffer($id);
        $this->assertInstanceOf(BookOffer::class, $offer);
        $this->assertEquals($offer->getIdentifier(), $id);
        $this->assertEquals($offer->getBarcode(), '+F20313804');
        $this->assertEquals($offer->getLibrary(), 'F1490');
        $this->assertEquals($offer->getLocation(), 'IBIBL');
        $this->assertEquals($offer->getLocationIdentifier(), '');
        $this->assertEquals($offer->getName(), 'Arch+ Zeitschrift für Architektur und Urbanismus');

        $book = $offer->getBook();
        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($book->getTitle(), 'Arch+ Zeitschrift für Architektur und Urbanismus');
        $this->assertEquals($book->getIdentifier(), '990002338910204517');
        $this->assertEquals($book->getAuthor(), '');
    }

    public function testGetBookOfferInvalID()
    {
        try {
            $this->api->getBookOffer('foo');
        } catch (ItemNotLoadedException $e) {
            $this->assertStringContainsString('Invalid identifier', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail();
        }
    }

    public function testGetBookOffers()
    {
        $this->mockResponses([
            new Response(200, ['Content-Type' => 'application/json;charset=UTF-8'], self::bookOfferResponse),
        ]);

        $barcode = '+F20313804';
        $offers = $this->api->getBookOffers(['barcode' => $barcode]);
        $this->assertIsArray($offers);
        $this->assertCount(1, $offers);
        $offer = $offers[0];
        $this->assertEquals($offer->getBarcode(), $barcode);
    }

    public function testTokenError()
    {
        $identifier = 'foo';
        $token = 'aBa4As-FassK0d21-1f3';
        $message = "Server error: `GET https://online.tugraz.at/tug_onlinej/ws/webservice_v1.0/cdm/organization/xml?token=$token&orgUnitID=677&language=de` resulted in a `503 Service Unavailable` response:";

        // check if token is replaced by "hidden"
        try {
            throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded because of XML error! Message: %s", $identifier, Tools::filterErrorMessage($message)));
        } catch (ItemNotLoadedException $e) {
            $this->assertStringContainsString('token=hidden', $e->getMessage());
        }

        // check if token isn't present
        try {
            throw new ItemNotLoadedException(sprintf("Organization with id '%s' could not be loaded because of XML error! Message: %s", $identifier, Tools::filterErrorMessage($message)));
        } catch (ItemNotLoadedException $e) {
            $this->assertStringNotContainsString($token, $e->getMessage());
        }
    }
}
