<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'ParcelDelivery',
    description: 'A parcel delivery',
    types: ['http://schema.org/ParcelDelivery'],
    operations: [
        new Get(
            uriTemplate: '/parcel-deliveries/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: DummyProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBookOrder:output'],
        'jsonld_embed_context' => true,
    ]
)]
class ParcelDelivery
{
    /**
     * We need an identifier otherwise when using the entity as part of other entities we will get an error:
     * No item route associated with the type "App\Entity\ParcelDelivery".
     *
     * We also need an `itemOperations={"get"}` or we will get an error:
     * No item route associated with the type "App\Entity\ParcelDelivery".
     *
     * @var string
     */
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var DeliveryEvent
     */
    #[ApiProperty(iris: ['http://schema.org/DeliveryEvent'])]
    #[Groups('LibraryBookOrder:output')]
    private $deliveryStatus;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getDeliveryStatus(): ?DeliveryEvent
    {
        return $this->deliveryStatus;
    }

    public function setDeliveryStatus(DeliveryEvent $deliveryEvent): self
    {
        $this->deliveryStatus = $deliveryEvent;

        return $this;
    }
}
