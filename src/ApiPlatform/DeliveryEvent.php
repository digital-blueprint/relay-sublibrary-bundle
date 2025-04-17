<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'DeliveryStatus',
    description: 'A delivery status',
    types: ['http://schema.org/DeliveryEvent'],
    operations: [
        new Get(
            uriTemplate: '/delivery-statuses/{identifier}',
            openapi: false,
            provider: DummyProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBookOrder:output'],
        'jsonld_embed_context' => true,
    ]
)]
class DeliveryEvent
{
    /**
     * We need an identifier otherwise when using the entity as part of other entities we will get an error:
     * No item route associated with the type "App\Entity\DeliveryStatus".
     *
     * We also need an `itemOperations={"get"}` or we will get an error:
     * No item route associated with the type "App\Entity\DeliveryStatus".
     *
     * @var string
     */
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['http://schema.org/DateTime'])]
    private $availableFrom;

    /**
     * @var EventStatusType
     */
    #[ApiProperty(iris: ['http://schema.org/EventStatusType'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $eventStatus;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getAvailableFrom(): ?\DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(\DateTimeInterface $availableFrom): self
    {
        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function getEventStatus(): ?EventStatusType
    {
        return $this->eventStatus;
    }

    public function setEventStatus(EventStatusType $eventStatus): self
    {
        $this->eventStatus = $eventStatus;

        return $this;
    }
}
