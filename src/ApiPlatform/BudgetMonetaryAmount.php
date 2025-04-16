<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'LibraryBudgetMonetaryAmount',
    description: 'A budget value of Sublibrary',
    types: ['https://schema.org/MonetaryAmount'],
    operations: [
        new GetCollection(
            uriTemplate: '/budget-monetary-amounts',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'sublibrary',
                        in: 'query',
                        description: 'Get all budget values of a sublibrary (ID of Sublibrary resource)',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '1190'
                    ),
                ]
            ),
            provider: BudgetMonetaryAmountProvider::class
        ),
        new Get(
            uriTemplate: '/budget-monetary-amounts/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: BudgetMonetaryAmountProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryBudgetMonetaryAmount:output'],
        'jsonld_embed_context' => true,
    ]
)]
class BudgetMonetaryAmount
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    private $identifier;

    /**
     * @var string
     */
    #[Groups(['LibraryBudgetMonetaryAmount:output'])]
    private $name;

    /**
     * @var float
     */
    #[ApiProperty(iris: ['http://schema.org/value'])]
    #[Groups(['LibraryBudgetMonetaryAmount:output'])]
    private $value;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/currency'])]
    #[Groups(['LibraryBudgetMonetaryAmount:output'])]
    private $currency;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }
}
