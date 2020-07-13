<?php

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={},
 *     itemOperations={"get"},
 *     iri="http://schema.org/Order",
 *     routePrefix="/orders",
 *     shortName="LibraryBookOrder",
 *     description="A book order from the library",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LibraryBookOrdersByOrganization"}},
 * )
 */
class BookOrder
{
    /**
     * @ApiProperty(identifier=true)
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/orderNumber")
     */
    private $orderNumber;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/Text")
     */
    private $receivingNote;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/OrderItem")
     */
    private $orderedItem;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     */
    private $orderStatus;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/DateTime")
     */
    private $orderDate;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getReceivingNote(): ?string
    {
        return $this->receivingNote;
    }

    public function setReceivingNote(string $receivingNote): self
    {
        $this->receivingNote = $receivingNote;

        return $this;
    }

    public function getOrderedItem(): ?BookOrderItem
    {
        return $this->orderedItem;
    }

    public function setOrderedItem(BookOrderItem $orderedItem): self
    {
        $this->orderedItem = $orderedItem;

        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): self
    {
        $this->orderStatus = $orderStatus;

        return $this;
    }

    public function getOrderDate(): ?DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }
}
