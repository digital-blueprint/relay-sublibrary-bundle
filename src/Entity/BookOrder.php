<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_LIBRARY_MANAGER')"},
 *     collectionOperations={},
 *     itemOperations={"get"},
 *     iri="http://schema.org/Order",
 *     routePrefix="/orders",
 *     shortName="LibraryBookOrder",
 *     description="A book order from the library",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LibraryBookOrder:output", "LibraryBookOrderItem:output", "LibraryBook:output", "ParcelDelivery:output"}},
 * )
 */
class BookOrder
{
    /**
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/orderNumber")
     *
     * @var string
     */
    private $orderNumber;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/Text")
     *
     * @var string
     */
    private $receivingNote;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/OrderItem")
     *
     * @var BookOrderItem
     */
    private $orderedItem;

    /**
     * @Groups({"LibraryBookOrder:output"})
     *
     * @var string
     */
    private $orderStatus;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/DateTime")
     *
     * @var DateTimeInterface
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
