<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Dbp\Relay\SublibraryBundle\Controller\GetLocationIdentifiersByBookOffer;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *     attributes={
 *         "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')"
 *     },
 *     collectionOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "path" = "/sublibrary/book-locations",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *         },
 *         "get_location_identifiers" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "method" = "GET",
 *             "path" = "/sublibrary/book-offers/{identifier}/location-identifiers",
 *             "controller" = GetLocationIdentifiersByBookOffer::class,
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *                 "summary" = "Retrieves all location identifiers with in the same holding and with the same location as the book offer.",
 *                 "parameters" = {
 *                     {"name" = "identifier", "in" = "path", "description" = "Id of book offer", "required" = true, "type" = "string"}
 *                 }
 *             }
 *         }
 *     },
 *     itemOperations={
 *         "get" = {
 *             "security" = "is_granted('IS_AUTHENTICATED_FULLY') and is_granted('ROLE_LIBRARY_MANAGER')",
 *             "path" = "/sublibrary/book-locations/{identifier}",
 *             "openapi_context" = {
 *                 "tags" = {"Sublibrary"},
 *             },
 *             "controller" = NotFoundAction::class,
 *             "read" = false,
 *             "output" = false,
 *         },
 *     },
 *     iri="https://schema.org/location",
 *     shortName="BookLocation",
 *     description="The location, where a book is shelved.",
 *     normalizationContext={
 *         "jsonld_embed_context" = true,
 *         "groups" = {"BookLocation:output"}
 *     },
 * )
 */
class BookLocation
{
    /**
     * @ApiProperty(identifier=true)
     * @Groups({"BookLocation:output"})
     *
     * @var string
     */
    private $identifier;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }
}
