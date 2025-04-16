<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use Dbp\Relay\BasePersonBundle\Entity\Person;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBookLoan',
    description: 'A book loan in the library',
    types: ['http://schema.org/LendAction'],
    operations: [
        new GetCollection(
            uriTemplate: '/book-loans',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'borrower',
                        in: 'query',
                        description: 'Get all book loans of a borrower (ID of BasePerson resource)',
                        schema: ['type' => 'string'],
                        example: 'woody007'
                    ),
                    new Parameter(
                        name: 'sublibrary',
                        in: 'query',
                        description: 'Get all book offers of a sublibrary (ID of Sublibrary resource)',
                        schema: ['type' => 'string'],
                        example: '1190'
                    ),
                ]
            ),
            provider: BookLoanProvider::class
        ),
        new Get(
            uriTemplate: '/book-loans/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookLoanProvider::class
        ),
        new Patch(
            uriTemplate: '/book-loans/{identifier}',
            inputFormats: [
                'jsonld' => ['application/merge-patch+json'],
            ],
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BookLoanProvider::class,
            processor: BookLoanProcessor::class
        ),
        new GetCollection(
            uriTemplate: '/book-offers/{identifier}/loans',
            controller: GetBookLoansByBookOffer::class,
            openapi: new Operation(
                tags: ['Sublibrary'],
                summary: 'Get the loans on a book offer.',
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'Id of book offer',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '991293320000541-2280429390003340-2380429400003340'
                    ),
                ]
            ),
            paginationEnabled: false,
            normalizationContext: [
                'groups' => ['LibraryBookLoan:output', 'LibraryBookOffer:output', 'LibraryBook:output'],
                'jsonld_embed_context' => true,
            ],
            read: false,
            name: 'get_loans_by_book_offer'
        ),
        new Post(
            uriTemplate: '/book-offers/{identifier}/loans',
            controller: PostBookLoanByBookOffer::class,
            openapi: new Operation(
                tags: ['Sublibrary'],
                summary: 'Post a loan for a book offer.',
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'Id of book offer',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '991293320000541-2280429390003340-2380429400003340'
                    ),
                ],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        'application/ld+json' => [
                            'schema' => [
                                'type' => 'object',
                                'example' => [
                                    'borrower' => '/base/people/woody007',
                                    'library' => 'F1490',
                                ],
                            ],
                        ],
                    ])
                )
            ),
            read: false,
            deserialize: false,
            validate: false,
            write: false,
            name: 'post_loan_by_book_offer'
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBookLoan:output', 'BasePerson:output', 'LibraryBookOffer:output', 'LibraryBook:output', 'LocalData:output'],
        'jsonld_embed_context' => true,
    ],
    denormalizationContext: [
        'groups' => ['LibraryBookLoan:input'],
    ]
)]
class BookLoan
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['LibraryBookLoan:output'])]
    private $identifier;

    /**
     * @var BookOffer
     */
    #[ApiProperty(iris: ['http://schema.org/Offer'])]
    #[Groups(['LibraryBookLoan:output'])]
    private $object;

    /**
     * @var Person
     */
    #[ApiProperty(iris: ['http://schema.org/Person'])]
    #[Groups(['LibraryBookLoan:output'])]
    private $borrower;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[Groups(['LibraryBookLoan:output'])]
    private $startTime;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[Groups(['LibraryBookLoan:output', 'LibraryBookLoan:input'])]
    private $endTime;

    /**
     * @var \DateTimeInterface
     */
    #[ApiProperty(iris: ['https://schema.org/DateTime'])]
    #[Groups(['LibraryBookLoan:output'])]
    private $returnTime;

    #[ApiProperty(iris: ['http://schema.org/Text'])]
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
