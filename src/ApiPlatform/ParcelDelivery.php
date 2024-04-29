<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

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
    private $identifier;

    /**
     * @var DeliveryEvent
     */
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
