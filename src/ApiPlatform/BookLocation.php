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
    shortName: 'BookLocation',
    description: 'The location, where a book is shelved.',
    types: ['https://schema.org/location'],
    operations: [
        new GetCollection(
            uriTemplate: '/book-locations',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: DummyProvider::class
        ),
        new Get(
            uriTemplate: '/book-locations/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary']
            ),
            provider: DummyProvider::class
        ),
        new GetCollection(
            uriTemplate: '/book-offers/{identifier}/location-identifiers',
            controller: GetLocationIdentifiersByBookOffer::class,
            openapi: new Operation(
                tags: ['Sublibrary'],
                summary: 'Retrieves all location identifiers with in the same holding and with the same location as the book offer.',
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'Id of book offer',
                        required: true,
                        schema: ['type' => 'string']
                    ),
                ]
            ),
            read: false,
            name: 'get_location_identifiers'
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['BookLocation:output'],
        'jsonld_embed_context' => true,
    ]
)]
class BookLocation
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['BookLocation:output'])]
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
