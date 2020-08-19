<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={"security"="is_granted('ROLE_F_BIB_F')"},
 *     collectionOperations={},
 *     itemOperations={"get"},
 *     iri="http://schema.org/EventStatusType",
 *     shortName="EventStatusType",
 *     description="A event status type",
 * )
 */
class EventStatusType
{
    /**
     * We need an identifier otherwise when using the entity as part of other entities we will get an error:
     * No item route associated with the type "App\Entity\EventStatusType".
     *
     * We also need an `itemOperations={"get"}` or we will get an error:
     * No item route associated with the type "App\Entity\EventStatusType".
     *
     * @ApiProperty(identifier=true)
     */
    private $identifier;

    /**
     * @Groups({"LibraryBookOrdersByOrganization"})
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
