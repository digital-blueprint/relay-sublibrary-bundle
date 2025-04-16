<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBookOrderItem',
    description: 'A book order item from the library',
    types: ['http://schema.org/OrderItem'],
    operations: [
        new Get(
            uriTemplate: '/book-order-items/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: DummyProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBookOrder:output'],
        'jsonld_embed_context' => true,
    ]
)]
class BookOrderItem
{
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var Book
     */
    #[ApiProperty(iris: ['http://schema.org/OrderItem'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $orderedItem;

    /**
     * @var ParcelDelivery
     */
    #[ApiProperty(iris: ['http://schema.org/ParcelDelivery'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $orderDelivery;

    /**
     * A BookOrderItem usually doesn't have a price, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     */
    #[ApiProperty(iris: ['http://schema.org/price'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $price;

    /**
     * A BookOrderItem usually doesn't have a priceCurrency, but we are assigning one, so we don't need to add another BookOffer layer to our book loan list.
     */
    #[ApiProperty(iris: ['http://schema.org/priceCurrency'])]
    #[Groups(['LibraryBookOrder:output'])]
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

    public function setPriceCurrency(string $priceCurrency): self
    {
        $this->priceCurrency = $priceCurrency;

        return $this;
    }
}
