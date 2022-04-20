<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get" = {
 *             "path" = "/sublibrary/sublibraries",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "parameters" = {
 *                     {"name" = "libraryManager", "in" = "query", "description" = "Get the Sublibraries the given person has library manager permissions for (ID of BasePerson resource)", "required" = true, "type" = "string", "example" = "woody007"},
 *                     {"name" = "lang", "in" = "query", "description" = "Language of result", "required" = false, "type" = "string", "enum" = {"de", "en"}, "example" = "de"}
 *                 }
 *             }
 *         },
 *     },
 *     itemOperations={
 *         "get" = {
 *             "path" = "/sublibrary/sublibraries/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "ID of Sublibrary", "required" = true, "type" = "string", "example" = "1190"},
 *                     {"name" = "lang", "in" = "query", "description" = "Language of result", "required" = false, "type" = "string", "enum" = {"de", "en"}, "example" = "de"}
 *                 }
 *             }
 *         },
 *     },
 *     iri="https://schema.org/Library",
 *     shortName="Sublibrary",
 *     description="Library of an Organization",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"Sublibrary:output"}
 *     }
 * )
 */
class Sublibrary
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"Sublibrary:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @ApiProperty(iri="https://schema.org/name")
     * @Groups({"Sublibrary:output"})
     *
     * @var string
     */
    private $name;

    /**
     * @ApiProperty(iri="https://schema.org/identifier")
     * @Groups({"Sublibrary:output"})
     *
     * @var string
     */
    private $code;

    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }
}
