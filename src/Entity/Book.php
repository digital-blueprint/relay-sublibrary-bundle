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
 *     attributes={"security"="is_granted('ROLE_F_BIB_F')"},
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     iri="http://schema.org/Book",
 *     routePrefix="/books",
 *     shortName="LibraryBook",
 *     description="A book from the library",
 *     normalizationContext={"jsonld_embed_context"=true}
 * )
 */
class Book
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LibraryBook"})
     */
    private $identifier;

    /**
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups({"LibraryBook", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization", "LibraryBookOrdersByOrganization"})
     */
    private $title;

    /**
     * @ApiProperty(iri="http://schema.org/isbn")
     * @Groups({"LibraryBookOrdersByOrganization"})
     */
    private $isbn;

    /**
     * @ApiProperty(iri="http://schema.org/author")
     * @Groups({"LibraryBook", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization", "LibraryBookOrdersByOrganization"})
     */
    private $author;

    /**
     * Note that we are using a string here.
     *
     * @ApiProperty(iri="http://schema.org/publisher")
     * @Groups({"LibraryBook", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     */
    private $publisher;

    /**
     * Note that Alma only has the year stored.
     *
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBook", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     */
    private $datePublished;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getISBN(): ?string
    {
        return $this->isbn;
    }

    public function setISBN(string $isbn): self
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getDatePublished(): ?DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(DateTimeInterface $datePublished): self
    {
        $this->datePublished = $datePublished;

        return $this;
    }
}
