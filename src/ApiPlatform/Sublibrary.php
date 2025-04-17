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
    shortName: 'Sublibrary',
    description: 'Library of an Organization',
    types: ['https://schema.org/Library'],
    operations: [
        new GetCollection(
            uriTemplate: '/sublibraries',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'libraryManager',
                        in: 'query',
                        description: 'Get the Sublibraries the given person has library manager permissions for (ID of BasePerson resource)',
                        required: false,
                        deprecated: true,
                        schema: ['type' => 'string']
                    ),
                    new Parameter(
                        name: 'lang',
                        in: 'query',
                        description: 'Language of result',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => ['de', 'en'],
                        ],
                        example: 'de'
                    ),
                ]
            ),
            provider: SublibraryProvider::class
        ),
        new Get(
            uriTemplate: '/sublibraries/{identifier}',
            openapi: new Operation(
                tags: ['Sublibrary'],
                parameters: [
                    new Parameter(
                        name: 'identifier',
                        in: 'path',
                        description: 'ID of Sublibrary',
                        required: true,
                        schema: ['type' => 'string'],
                        example: '1190'
                    ),
                    new Parameter(
                        name: 'lang',
                        in: 'query',
                        description: 'Language of result',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => ['de', 'en'],
                        ],
                        example: 'de'
                    ),
                ]
            ),
            provider: SublibraryProvider::class
        ),
    ],
    routePrefix: '/sublibrary',
    normalizationContext: [
        'groups' => ['Sublibrary:output'],
        'jsonld_embed_context' => true,
    ]
)]
class Sublibrary
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    #[Groups(['Sublibrary:output'])]
    private $identifier;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/name'])]
    #[Groups(['Sublibrary:output'])]
    private $name;

    /**
     * @var string
     */
    #[ApiProperty(iris: ['http://schema.org/identifier'])]
    #[Groups(['Sublibrary:output'])]
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
