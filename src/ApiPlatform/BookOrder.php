<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBookOrder',
    description: 'A book order from the library',
    types: ['http://schema.org/Order'],
    operations: [
        new GetCollection(
            uriTemplate: '/book-orders',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'sublibrary',
                        in: 'query',
                        description: 'Get all book offers of a sublibrary (ID of Sublibrary resource)',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '1190'
                    ),
                ]
            ),
            provider: BookOrderProvider::class
        ),
        new Get(
            uriTemplate: '/book-orders/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookOrderProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBookOrder:output'],
        'jsonld_embed_context' => true,
    ]
)]
class BookOrder
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/orderNumber'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $orderNumber;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/Text'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $receivingNote;

    /**
     * @var BookOrderItem
     */
    #[ApiProperty(iris: ['http://schema.org/OrderItem'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $orderedItem;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/Text'])]
    #[Groups(['LibraryBookOrder:output'])]
    private $orderStatus;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['http://schema.org/DateTime'])]
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
