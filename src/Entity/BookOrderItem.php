<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

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
 *             "path" = "/sublibrary/book-order-items/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/OrderItem",
 *     shortName="LibraryBookOrderItem",
 *     description="A book order item from the library",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"LibraryBookOrder:output"}
 *     },
 * )
 */
class BookOrderItem
{
    /**
     * @ApiProperty(identifier=true)
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/OrderItem")
     *
     * @var Book
     */
    private $orderedItem;

    /**
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/ParcelDelivery")
     *
     * @var ParcelDelivery
     */
    private $orderDelivery;

    /**
     * A BookOrderItem usually doesn't have a price, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     *
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/price")
     */
    private $price;

    /**
     * A BookOrderItem usually doesn't have a priceCurrency, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     *
     * @Groups({"LibraryBookOrder:output"})
     * @ApiProperty(iri="http://schema.org/priceCurrency")
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
