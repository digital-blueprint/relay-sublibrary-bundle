<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Sublibrary;

interface SublibraryInterface
{
    public function getIdentifier(): string;

    public function getName(): string;

    public function getCode(): string;
}
