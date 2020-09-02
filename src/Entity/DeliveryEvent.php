<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_F_BIB_F')"},
 *     collectionOperations={},
 *     itemOperations={"get"},
 *     iri="http://schema.org/DeliveryEvent",
 *     shortName="DeliveryStatus",
 *     description="A delivery status",
 * )
 */
class DeliveryEvent
{
    /**
     * We need an identifier otherwise when using the entity as part of other entities we will get an error:
     * No item route associated with the type "App\Entity\DeliveryStatus".
     *
     * We also need an `itemOperations={"get"}` or we will get an error:
     * No item route associated with the type "App\Entity\DeliveryStatus".
     *
     * @ApiProperty(identifier=true)
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/DateTime")
     */
    private $availableFrom;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/EventStatusType ")
     */
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

    public function getAvailableFrom(): ?DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(DateTimeInterface $availableFrom): self
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
