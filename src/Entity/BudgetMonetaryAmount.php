<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/library_budget_monetary_amounts",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Alma"},
 *                 "parameters" = {
 *                     {"name" = "organization", "in" = "query", "Search for all budget monetary amounts of an organization", "type" = "string", "example" = "681-F1490"}
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/library_budget_monetary_amounts/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Alma"},
 *             },
 *         }
 *     },
 *     iri="https://schema.org/MonetaryAmount",
 *     shortName="LibraryBudgetMonetaryAmount",
 *     description="A budget value of an organization in the library",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"LibraryBudgetMonetaryAmount:output"}
 *     },
 * )
 */
class BudgetMonetaryAmount
{
    /**
     * @ApiProperty(identifier=true)
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
     * @ApiProperty(iri="http://schema.org/name")
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
     * @ApiProperty(iri="http://schema.org/value")
     *
     * @var float
     */
    private $value;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
     * @ApiProperty(iri="http://schema.org/currency")
     *
     * @var string
     */
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
