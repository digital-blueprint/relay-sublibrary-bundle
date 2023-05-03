<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Dbp\Relay\SublibraryBundle\Controller\GetBookLoansByBookOffer;
use Dbp\Relay\SublibraryBundle\Controller\PostBookLoanByBookOffer;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "path" = "/sublibrary/book-loans",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "parameters" = {
 *                     {"name" = "borrower", "in" = "query", "description" = "Get all book loans of a borrower (ID of BasePerson resource)", "type" = "string", "example" = "woody007"},
 *                     {"name" = "sublibrary", "in" = "query", "description" = "Get all book loans at a sublibrary (ID of Sublibrary resource)", "type" = "string", "example" = "1190"},
 *                 }
 *             }
 *         },
 *         "get_loans_by_book_offer" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "method" = "GET",
 *             "path" = "/sublibrary/book-offers/{identifier}/loans",
 *             "controller" = GetBookLoansByBookOffer::class,
 *             "read" = false,
 *             "pagination_enabled" = false,
 *             "normalization_context" = {
 *                 "jsonld_embed_context" = true,
 *                 "groups" = {"LibraryBookLoan:output", "LibraryBookOffer:output", "LibraryBook:output"}
 *             },
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "summary" = "Get the loans on a book offer.",
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "Id of book offer", "required" = true, "type" = "string", "example" = "991293320000541-2280429390003340-2380429400003340"}
 *                 }
 *             },
 *         },
 *         "post_loan_by_book_offer" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "method" = "POST",
 *             "path" = "/sublibrary/book-offers/{identifier}/loans",
 *             "controller" = PostBookLoanByBookOffer::class,
 *             "read" = false,
 *             "write" = false,
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "summary" = "Post a loan for a book offer.",
 *                 "requestBody" = {
 *                     "content" = {
 *                         "application/json" = {
 *                             "schema" = {"type" = "object"},
 *                             "example" = {"borrower" = "/base/people/woody007", "library" = "F1490"}
 *                         }
 *                     }
 *                 },
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "Id of book offer", "required" = true, "type" = "string", "example" = "991293320000541-2280429390003340-2380429400003340"},
 *                 }
 *             },
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "path" = "/sublibrary/book-loans/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         },
 *         "put" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "path" = "/sublibrary/book-loans/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         },
 *     },
 *     iri="http://schema.org/LendAction",
 *     shortName="LibraryBookLoan",
 *     description="A book loan in the library",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"LibraryBookLoan:output", "BasePerson:output", "LibraryBookOffer:output", "LibraryBook:output", "LocalData:output"}
 *     },
 *     denormalizationContext={
 *         "groups" = {"LibraryBookLoan:input"}
 *     }
 * )
 */
class BookLoan
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LibraryBookLoan:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @var BookOffer
     * @ApiProperty(iri="http://schema.org/Offer")
     * @Groups({"LibraryBookLoan:output"})
     */
    private $object;

    /**
     * @var Person
     * @ApiProperty(iri="http://schema.org/Person")
     * @Groups({"LibraryBookLoan:output"})
     */
    private $borrower;

    /**
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoan:output"})
     */
    private $startTime;

    /**
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoan:input"})
     */
    private $endTime;

    /**
     * TODO: there is no returnTime in the http://schema.org/LendAction schema!
     *
     * @var DateTimeInterface
     * @ApiProperty(iri="https://schema.org/DateTime")
     * @Groups({"LibraryBookLoan:output"})
     */
    private $returnTime;

    /**
     * @Groups({"LibraryBookLoan:output", "LibraryBookLoan:input"})
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

    /*
     * returns the library code of this book loans book offer.
     */
    public function getLibrary(): ?string
    {
        return $this->object ? $this->object->getLibrary() : null;
    }
}
