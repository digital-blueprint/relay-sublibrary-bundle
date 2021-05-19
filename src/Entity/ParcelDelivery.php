<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={},
 *     itemOperations={
 *         "get" = {
 *             "path" = "/parcel_deliveries/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Alma"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/ParcelDelivery",
 *     shortName="ParcelDelivery",
 *     description="A parcel delivery",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"ParcelDelivery:output", "DeliveryEvent:output", "EventStatusType:output"}
 *     },
 * )
 */
class ParcelDelivery
{
    /**
     * We need an identifier otherwise when using the entity as part of other entities we will get an error:
     * No item route associated with the type "App\Entity\ParcelDelivery".
     *
     * We also need an `itemOperations={"get"}` or we will get an error:
     * No item route associated with the type "App\Entity\ParcelDelivery".
     *
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"ParcelDelivery:output"})
     * @ApiProperty(iri="http://schema.org/DeliveryEvent")
     *
     * @var DeliveryEvent
     */
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
