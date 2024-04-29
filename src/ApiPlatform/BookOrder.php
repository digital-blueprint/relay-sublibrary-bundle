<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class BookOrder
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    #[Groups(['LibraryBookOrder:output'])]
    private $orderNumber;

    /**
     * @var string
     */
    #[Groups(['LibraryBookOrder:output'])]
    private $receivingNote;

    /**
     * @var BookOrderItem
     */
    #[Groups(['LibraryBookOrder:output'])]
    private $orderedItem;

    /**
     * @var string
     */
    #[Groups(['LibraryBookOrder:output'])]
    private $orderStatus;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['LibraryBookOrder:output'])]
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

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }
}
