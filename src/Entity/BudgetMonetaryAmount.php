<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

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
 *             "path" = "/sublibrary/budget-monetary-amounts",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "parameters" = {
 *                     {"name" = "sublibrary", "in" = "query", "Get all budget values of a sublibrary (ID of Sublibrary resource)", "required" = true, "type" = "string", "example" = "1190"}
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/sublibrary/budget-monetary-amounts/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         }
 *     },
 *     iri="https://schema.org/MonetaryAmount",
 *     shortName="LibraryBudgetMonetaryAmount",
 *     description="A budget value of Sublibrary",
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
