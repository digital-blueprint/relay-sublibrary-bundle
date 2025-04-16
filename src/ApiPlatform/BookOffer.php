<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBookOffer',
    description: 'A book to lend from the library',
    types: ['http://schema.org/Offer'],
    operations: [
        new GetCollection(
            uriTemplate: '/book-offers',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'barcode',
                        in: 'query',
                        description: 'Search for a book offer by barcode',
                        schema: ['type' => 'string']
                    ),
                    new Parameter(
                        name: 'sublibrary',
                        in: 'query',
                        description: 'Get all book offers of a sublibrary (ID of Sublibrary resource)',
                        schema: ['type' => 'string'],
                        example: '1190'
                    ),
                ]
            ),
            provider: BookOfferProvider::class
        ),
        new Get(
            uriTemplate: '/book-offers/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookOfferProvider::class
        ),
        new Patch(
            uriTemplate: '/book-offers/{identifier}',
            inputFormats: [
                'jsonld' => ['application/merge-patch+json'],
            ],
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookOfferProvider::class,
            processor: BookOfferProcessor::class
        ),
        new Post(
            uriTemplate: '/book-offers/{identifier}/return',
            controller: BookOfferPostReturn::class,
            openapi: new Operation(
                tags: ['Sublibrary'],
                summary: 'Return a book offer.',
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'Id of book offer',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '991293320000541-2280429390003340-2380429400003340'
                    ),
                ],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                'type' => 'object',
                                'example' => '{}',
                            ],
                        ],
                    ])
                )
            ),
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            name: 'post_return'
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBook:output', 'LibraryBookOffer:output'],
        'jsonld_embed_context' => true,
    ],
    denormalizationContext: [
        'groups' => ['LibraryBookOffer:input'],
    ]
)]
class BookOffer
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['LibraryBookOffer:output'])]
    private $identifier;

    /**
     * @var Book
     */
    #[ApiProperty(iris: ['http://schema.org/Book'])]
    #[Groups(['LibraryBookOffer:output'])]
    private $book;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/serialNumber'])]
    #[Groups(['LibraryBookOffer:output'])]
    private $barcode;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/Text'])]
    #[Groups(['LibraryBookOffer:output', 'LibraryBookOffer:input'])]
    private $locationIdentifier;

    /**
     * e.g. "F4480" organization code (orgUnitCode)
     * TODO: in theory we could use a "http://schema.org/offeredBy", but we would need the orgUnitID for that, which Alma wouldn't provide.
     *
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/Text'])]
    #[Groups(['LibraryBookOffer:output', 'LibraryBookOffer:input'])]
    private $library;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/Text'])]
    #[Groups(['LibraryBookOffer:output', 'LibraryBookOffer:input'])]
    private $location;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/description'])]
    #[Groups(['LibraryBookOffer:output', 'LibraryBookOffer:input'])]
    private $description;

    #[ApiProperty(iris: ['http://schema.org/availabilityStarts'])]
    #[Groups(['LibraryBookOffer:output', 'LibraryBookOffer:input'])]
    private $availabilityStarts;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): self
    {
        $this->barcode = $barcode;

        return $this;
    }

    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Groups(['LibraryBookOffer:output'])]
    public function getName(): ?string
    {
        $author = $this->book->getAuthor();
        if (!$author) {
            return $this->book->getTitle();
        }

        return "{$this->book->getTitle()} ({$author})";
    }

    public function getLocationIdentifier(): ?string
    {
        return $this->locationIdentifier;
    }

    public function setLocationIdentifier(string $locationIdentifier): self
    {
        $this->locationIdentifier = $locationIdentifier;

        return $this;
    }

    /**
     * returns the library code of this book offer.
     */
    public function getLibrary(): ?string
    {
        return $this->library;
    }

    public function setLibrary(string $library): self
    {
        $this->library = $library;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getAvailabilityStarts(): ?\DateTimeInterface
    {
        return $this->availabilityStarts;
    }

    public function setAvailabilityStarts(\DateTimeInterface $availabilityStarts): self
    {
        $this->availabilityStarts = $availabilityStarts;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
