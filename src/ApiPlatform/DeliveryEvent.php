<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

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
    private $identifier;

    /**
     * @var \DateTimeInterface
     */
    private $availableFrom;

    /**
     * @Groups({"LibraryBookOrder:output"})
     *
     * @var EventStatusType
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
