<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Sublibrary
{
    /**
     * @Groups({"Sublibrary:output"})
     *
     * @var string
     */
    private $identifier;

    /**
     * @Groups({"Sublibrary:output"})
     *
     * @var string
     */
    private $name;

    /**
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
