<?php

namespace DBP\API\AlmaBundle\Tests\DataPersister;

use DBP\API\AlmaBundle\DataPersister\BookOfferDataPersister;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\AlmaBundle\Service\AlmaApi;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookOfferDataPersisterTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    public function testMock()
    {
        // TODO: fix me
        $this->markTestIncomplete('This test has not been implemented yet.');

        return;

        // TODO: AlmaApi needs Security class
        $mock = Mockery::mock(AlmaApi::class)->makePartial();
        $persister = new BookOfferDataPersister($mock);

        $offer = new BookOffer();
        $this->assertTrue($persister->supports($offer));
        $mock->shouldReceive('updateBookOffer')->once()->andReturnUndefined();
        $result = $persister->persist($offer);
        $this->assertSame($result, $offer);
    }
}
