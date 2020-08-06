<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use DBP\API\CoreBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *     "get"={"openapi_context"={
 *       "parameters"={
 *         {"name"="name", "in"="query", "description"="Search for all loans of a person", "type"="string", "example"="woody007"},
 *         {"name"="organization", "in"="query", "description"="Search for all loans of on organization", "type"="string", "example"="681-F1490"},
 *     }}}},
 *     itemOperations={"get", "put"},
 *     iri="http://schema.org/LendAction",
 *     routePrefix="/loans",
 *     shortName="LibraryBookLoan",
 *     description="A book loan in the library",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LibraryBookLoan", "LibraryBookLoan:output", "LDAPPerson", "LibraryBookOffer"}},
 *     denormalizationContext={"groups"={"LibraryBookLoan:input"}}
 * )
 */
class BookLoan
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LibraryBookLoan:output"})
     */
    private $identifier;

    /**
     * @var BookOffer
     * @ApiProperty(iri="http://schema.org/Offer")
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization"})
     */
    private $object;

    /**
     * @var Person
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoanByOrganization"})
     */
    private $borrower;

    /**
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization"})
     */
    private $startTime;

    /**
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoanByPerson", "LibraryBookLoan:input", "LibraryBookLoanByOrganization"})
     */
    private $endTime;

    /**
     * TODO: there is no returnTime in the http://schema.org/LendAction schema!
     *
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoanByOrganization"})
     */
    private $returnTime;

    /**
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoanByPerson", "LibraryBookLoan:input"})
     */
    private $loanStatus;

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

    public function getStartTime(): ?DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getReturnTime(): ?DateTimeInterface
    {
        return $this->returnTime;
    }

    public function setReturnTime(DateTimeInterface $returnTime): self
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

    public function getLibrary(): ?string
    {
        return $this->object ? $this->object->getLibrary() : null;
    }
}
