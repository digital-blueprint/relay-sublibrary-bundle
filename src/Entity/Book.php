<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Example ids: 990002338910204517, 990003577070204517.
 *
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *         }
 *     },
 *     iri="http://schema.org/Book",
 *     routePrefix="/books",
 *     shortName="LibraryBook",
 *     description="A book from the library",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"LibraryBook:output"}
 *     }
 * )
 */
class Book
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LibraryBook:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"LibraryBook:output"})
     *
     * @var string
     */
    private $title;

    /**
     * @ApiProperty(iri="http://schema.org/isbn")
     * @Groups({"LibraryBook:output"})
     *
     * @var string
     */
    private $isbn;

    /**
     * @ApiProperty(iri="http://schema.org/author")
     * @Groups({"LibraryBook:output"})
     *
     * @var string
     */
    private $author;

    /**
     * Note that we are using a string here.
     *
     * @ApiProperty(iri="http://schema.org/publisher")
     * @Groups({"LibraryBook:output"})
     *
     * @var string;
     */
    private $publisher;

    /**
     * Note that Alma only has the year stored.
     *
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBook:output"})
     *
     * @var DateTimeInterface;
     */
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

    public function getISBN(): ?string
    {
        return $this->isbn;
    }

    public function setISBN(string $isbn): void
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

    public function getDatePublished(): ?DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(DateTimeInterface $datePublished): void
    {
        $this->datePublished = $datePublished;
    }
}
