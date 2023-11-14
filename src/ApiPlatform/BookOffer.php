<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class BookOffer
{
    /**
     * @Groups({"LibraryBookOffer:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOffer:output"})
     *
     * @var Book
     */
    private $book;

    /**
     * @Groups({"LibraryBookOffer:output"})
     *
     * @var string
     */
    private $barcode;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input"})
     *
     * @var string
     */
    private $locationIdentifier;

    /**
     * e.g. "F4480" organization code (orgUnitCode)
     * TODO: in theory we could use a "http://schema.org/offeredBy", but we would need the orgUnitID for that, which Alma wouldn't provide.
     *
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input"})
     *
     * @var string
     */
    private $library;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input"})
     *
     * @var string
     */
    private $location;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input"})
     *
     * @var string
     */
    private $description;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input"})
     */
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

    /**
     * @Groups({"LibraryBookOffer:output"})
     */
    public function getName(): ?string
    {
        $author = $this->book->getAuthor();
        if (!$author) {
            return $this->book->getTitle();
        }

        return "{$this->book->getTitle()} ({$author})";
    }

    /**
     * @return string
     */
    public function getLocationIdentifier(): ?string
    {
        return $this->locationIdentifier;
    }

    /**
     * @return BookOffer
     */
    public function setLocationIdentifier(string $locationIdentifier): self
    {
        $this->locationIdentifier = $locationIdentifier;

        return $this;
    }

    /**
     * returns the library code of this book offer.
     *
     * @return string
     */
    public function getLibrary(): ?string
    {
        return $this->library;
    }

    /**
     * @return BookOffer
     */
    public function setLibrary(string $library): self
    {
        $this->library = $library;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * @return BookOffer
     */
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

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return BookOffer
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
