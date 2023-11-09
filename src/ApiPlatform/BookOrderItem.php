<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class BookOrderItem
{
    private $identifier;

    /**
     * @Groups({"LibraryBookOrder:output"})
     *
     * @var Book
     */
    private $orderedItem;

    /**
     * @Groups({"LibraryBookOrder:output"})
     *
     * @var ParcelDelivery
     */
    private $orderDelivery;

    /**
     * A BookOrderItem usually doesn't have a price, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     *
     * @Groups({"LibraryBookOrder:output"})
     */
    private $price;

    /**
     * A BookOrderItem usually doesn't have a priceCurrency, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     *
     * @Groups({"LibraryBookOrder:output"})
     */
    private $priceCurrency;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getOrderedItem(): ?Book
    {
        return $this->orderedItem;
    }

    public function setOrderedItem(Book $orderedItem): self
    {
        $this->orderedItem = $orderedItem;

        return $this;
    }

    public function getOrderDelivery(): ?ParcelDelivery
    {
        return $this->orderDelivery;
    }

    public function setOrderDelivery(ParcelDelivery $orderDelivery): self
    {
        $this->orderDelivery = $orderDelivery;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     *
     * @return BookOrderItem
     */
    public function setPrice($price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }

    /**
     * @return BookOrderItem
     */
    public function setPriceCurrency(string $priceCurrency): self
    {
        $this->priceCurrency = $priceCurrency;

        return $this;
    }
}
