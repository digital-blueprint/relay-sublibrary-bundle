<?php

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={},
 *     itemOperations={"get"},
 *     iri="http://schema.org/ParcelDelivery",
 *     shortName="ParcelDelivery",
 *     description="A parcel delivery",
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
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/DeliveryEvent")
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
