<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use DBP\API\AlmaBundle\Controller\GetBookLoansByBookOffer;
use DBP\API\AlmaBundle\Controller\GetLocationIdentifiersByBookOffer;
use DBP\API\AlmaBundle\Controller\PostBookLoanByBookOffer;
use DBP\API\AlmaBundle\Controller\PostReturnByBookOffer;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Example id: 990003577070204517-2211897620004517-2311897610004517.
 *
 * @ApiResource(
 *     collectionOperations={
 *      "get"={"openapi_context"={"parameters"={
 *                               {"name"="barcode", "in"="query", "description"="Search for a book offer by barcode", "type"="string"}}}}
 *     },
 *     itemOperations={
 *      "get",
 *      "put"={"access_control"="is_granted('ROLE_F_BIB_F')", "access_control_message"="Only a 'Bibliotheksbeauftragter' can edit book offers."},
 *      "get_location_identifiers"={
 *         "method"="GET",
 *         "path"="/library_book_offers/{id}/location_identifiers",
 *         "controller"=GetLocationIdentifiersByBookOffer::class,
 *         "normalization_context"={"groups"={"LibraryBookOffer:output"}},
 *         "openapi_context"=
 *           {"summary"="Retrieves all location identifiers with in the same holding and with the same location as the book offer.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of book offer", "required"="true", "type"="string"}}},
 *      },
 *      "post_loan"={
 *         "method"="POST",
 *         "path"="/library_book_offers/{id}/loans",
 *         "controller"=PostBookLoanByBookOffer::class,
 *         "defaults":{"_api_persist"=false},
 *         "normalization_context"={"groups"={"LibraryBookLoan:output"}},
 *         "openapi_context"=
 *           {"summary"="Post a loan for a book offer.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of book offer", "required"="true", "type"="string", "example"="991293320000541-2280429390003340-2380429400003340"},
 *                          {"name"="body", "in"="body", "description"="Data", "required"="true", "type"="string", "example"={"borrower"="/people/woody007"}}}},
 *      },
 *      "post_return"={
 *         "method"="POST",
 *         "path"="/library_book_offers/{id}/return",
 *         "controller"=PostReturnByBookOffer::class,
 *         "defaults":{"_api_persist"=false},
 *         "normalization_context"={"groups"={"LibraryBookOffer:output"}},
 *         "openapi_context"=
 *           {"summary"="Return a book offer.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of book offer", "required"="true", "type"="string", "example"="991293320000541-2280429390003340-2380429400003340"},
 *                          {"name"="body", "in"="body", "description"="Data", "required"="true", "type"="string", "example"={}}}},
 *      },
 *      "get_loans"={
 *         "method"="GET",
 *         "path"="/library_book_offers/{id}/loans",
 *         "controller"=GetBookLoansByBookOffer::class,
 *         "normalization_context"={"groups"={"LibraryBookLoan:output"}},
 *         "openapi_context"=
 *           {"summary"="Get the loans on a book offer.",
 *            "parameters"={{"name"="id", "in"="path", "description"="Id of book offer", "required"="true", "type"="string", "example"="991293320000541-2280429390003340-2380429400003340"}}},
 *      }
 *     },
 *     iri="http://schema.org/Offer",
 *     routePrefix="/offers",
 *     shortName="LibraryBookOffer",
 *     description="A book to lend from the library",
 *     normalizationContext={"jsonld_embed_context"=true, "groups"={"LibraryBook", "LibraryBookOffer:output"}},
 *     denormalizationContext={"groups"={"LibraryBookOffer:input"}}
 * )
 */
class BookOffer
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"LibraryBookOffer:output"})
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBook", "LibraryBookLoan", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     * @ApiProperty(iri="http://schema.org/Book")
     *
     * @var Book
     */
    private $book;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     * @ApiProperty(iri="http://schema.org/serialNumber")
     */
    private $barcode;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     */
    private $locationIdentifier;

    /**
     * e.g. "F4480" organization code (orgUnitCode)
     * TODO: in theory we could use a "http://schema.org/offeredBy", but we would need the orgUnitID for that, which Alma wouldn't provide.
     *
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     */
    private $library;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     */
    private $location;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     * @ApiProperty(iri="http://schema.org/description")
     */
    private $description;

    /**
     * @Groups({"LibraryBookOffer:output", "LibraryBookOffer:input", "LibraryBookLoanByPerson", "LibraryBookLoanByOrganization", "LibraryBookOfferByOrganization"})
     * @ApiProperty(iri="http://schema.org/availabilityStarts")
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
     * @Groups({"LibraryBookOffer:output", "LibraryBook", "LibraryBookLoan", "LibraryBookLoanByPerson", "LibraryBookOfferByOrganization", "LibraryBookLoanByOrganization"})
     * @ApiProperty(iri="http://schema.org/name")
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
     * @return mixed
     */
    public function getLocationIdentifier()
    {
        return $this->locationIdentifier;
    }

    /**
     * @param mixed $locationIdentifier
     *
     * @return BookOffer
     */
    public function setLocationIdentifier($locationIdentifier): self
    {
        $this->locationIdentifier = $locationIdentifier;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @param mixed $library
     *
     * @return BookOffer
     */
    public function setLibrary($library): self
    {
        $this->library = $library;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     *
     * @return BookOffer
     */
    public function setLocation($location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getAvailabilityStarts(): ?DateTimeInterface
    {
        return $this->availabilityStarts;
    }

    public function setAvailabilityStarts(DateTimeInterface $availabilityStarts): self
    {
        $this->availabilityStarts = $availabilityStarts;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return BookOffer
     */
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }
}
