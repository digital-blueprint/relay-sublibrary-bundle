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
 *     collectionOperations={},
 *     itemOperations={
 *         "get" = {
 *             "path" = "/event_status_types/{identifier}",
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         }
 *     },
 *     iri="http://schema.org/EventStatusType",
 *     shortName="EventStatusType",
 *     description="A event status type",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"EventStatusType:output"}
 *     },
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
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"EventStatusType:output"})
     * @ApiProperty(iri="http://schema.org/name")
     *
     * @var string
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
