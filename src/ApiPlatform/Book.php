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
     * @var string
     */
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $title;

    /**
     * @var string
     */
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $isbn;

    /**
     * @var string
     */
    #[Groups(['LibraryBook:output', 'LibraryBookOrder:output'])]
    private $author;

    /**
     * Note that we are using a string here.
     *
     * @var string;
     */
    #[Groups(['LibraryBook:output'])]
    private $publisher;

    /**
     * Note that Alma only has the year stored.
     *
     * @var DateTimeInterface;
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

    public function getDatePublished(): ?\DateTimeInterface
    {
        return $this->datePublished;
    }

    public function setDatePublished(\DateTimeInterface $datePublished): void
    {
        $this->datePublished = $datePublished;
    }
}
