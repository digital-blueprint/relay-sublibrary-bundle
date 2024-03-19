<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\Groups;

class Book
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"LibraryBook:output", "LibraryBookOrder:output"})
     *
     * @var string
     */
    private $title;

    /**
     * @Groups({"LibraryBook:output", "LibraryBookOrder:output"})
     *
     * @var string
     */
    private $isbn;

    /**
     * @Groups({"LibraryBook:output", "LibraryBookOrder:output"})
     *
     * @var string
     */
    private $author;

    /**
     * Note that we are using a string here.
     *
     * @Groups({"LibraryBook:output"})
     *
     * @var string;
     */
    private $publisher;

    /**
     * Note that Alma only has the year stored.
     *
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

    public function getDatePublished(): ?\DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(\DateTimeInterface $datePublished): void
    {
        $this->datePublished = $datePublished;
    }
}
