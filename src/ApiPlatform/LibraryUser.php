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
    shortName: 'LibraryUser',
    description: 'A user in the library',
    types: ['https://schema.org/Person'],
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'search',
                        in: 'query',
                        description: 'Search users',
                        required: false,
                        schema: ['type' => 'string'],
                        example: 'Jane Doe'
                    ),
                ]
            ),
            provider: LibraryUserProvider::class
        ),
        new Get(
            uriTemplate: '/users/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'Alma ID of LibraryUser',
                        required: true,
                        schema: ['type' => 'string']
                    ),
                ]
            ),
            provider: LibraryUserProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['LibraryUser:output'],
        'jsonld_embed_context' => true,
    ]
)]
class LibraryUser
{
    #[ApiProperty(identifier: true)]
    #[Groups(['LibraryUser:output'])]
    private ?string $identifier = null;

    #[ApiProperty(iris: ['https://schema.org/givenName'])]
    #[Groups(['LibraryUser:output'])]
    private ?string $givenName = null;

    #[ApiProperty(iris: ['https://schema.org/familyName'])]
    #[Groups(['LibraryUser:output'])]
    private ?string $familyName = null;

    #[ApiProperty(iris: ['https://schema.org/email'])]
    #[Groups(['LibraryUser:output'])]
    private ?string $email = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): void
    {
        $this->familyName = $familyName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
