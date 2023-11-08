<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use Symfony\Component\Serializer\Annotation\Groups;

class BudgetMonetaryAmount
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
     *
     * @var float
     */
    private $value;

    /**
     * @Groups({"LibraryBudgetMonetaryAmount:output"})
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
