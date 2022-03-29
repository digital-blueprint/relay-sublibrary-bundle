<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get" = {
 *             "path" = "/delivery_statuses/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/DeliveryEvent",
 *     shortName="DeliveryStatus",
 *     description="A delivery status",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"DeliveryStatus:output", "EventStatusType:output"}
 *     },
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
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"DeliveryStatus:output"})
     * @ApiProperty(iri="http://schema.org/DateTime")
     *
     * @var DateTimeInterface
     */
    private $availableFrom;

    /**
     * @Groups({"DeliveryStatus:output"})
     * @ApiProperty(iri="http://schema.org/EventStatusType ")
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
