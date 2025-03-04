<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Dbp\Relay\BasePersonBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;

class BookLoan
{
    /**
     * @var string
     */
    #[Groups(['LibraryBookLoan:output'])]
    private $identifier;

    /**
     * @var BookOffer
     */
    #[Groups(['LibraryBookLoan:output'])]
    private $object;

    /**
     * @var Person
     */
    #[Groups(['LibraryBookLoan:output'])]
    private $borrower;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['LibraryBookLoan:output'])]
    private $startTime;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['LibraryBookLoan:output', 'LibraryBookLoan:input'])]
    private $endTime;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['LibraryBookLoan:output'])]
    private $returnTime;

    #[Groups(['LibraryBookLoan:output', 'LibraryBookLoan:input'])]
    private $loanStatus;

    private ?string $library = null;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getObject(): ?BookOffer
    {
        return $this->object;
    }

    public function setObject(BookOffer $object): self
    {
        $this->object = $object;

        return $this;
    }

    public function getBorrower(): ?Person
    {
        return $this->borrower;
    }

    public function setBorrower(Person $borrower): self
    {
        $this->borrower = $borrower;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getReturnTime(): ?\DateTimeInterface
    {
        return $this->returnTime;
    }

    public function setReturnTime(\DateTimeInterface $returnTime): self
    {
        $this->returnTime = $returnTime;

        return $this;
    }

    public function getLoanStatus(): ?string
    {
        return $this->loanStatus;
    }

    public function setLoanStatus(string $loanStatus): self
    {
        $this->loanStatus = $loanStatus;

        return $this;
    }

    public function setLibrary(string $library): void
    {
        $this->library = $library;
    }

    public function getLibrary(): ?string
    {
        return $this->library;
    }
}
