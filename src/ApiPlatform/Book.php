<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBook',
    description: 'A book from the library',
    types: ['http://schema.org/Book'],
    operations: [
        new GetCollection(
            uriTemplate: '/books',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookProvider::class
        ),
        new Get(
            uriTemplate: '/books/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBook:output'],
        'jsonld_embed_context' => true,
    ]
)]
class Book
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $title;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/isbn'])]
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $isbn;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/author'])]
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $author;

    /**
     * Note that we are using a string here.
     *
     * @var string
     */
    #[ApiProperty(iris: ['https://schema.org/publisher'])]
    #[Groups(['LibraryBook:output'])]
    private $publisher;

    /**
     * Note that Alma only has the year stored.
     *
     * @var ?string
     */
    #[Groups(['LibraryBook:output'])]
    private $datePublished;

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): void
    {
        $this->isbn = $isbn;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function getDatePublished(): ?string
    {
        return $this->datePublished;
    }

    public function setDatePublished(?string $datePublished): void
    {
        $this->datePublished = $datePublished;
    }
}
