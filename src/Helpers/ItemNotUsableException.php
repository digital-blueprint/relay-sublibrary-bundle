<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Helpers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ItemNotUsableException extends HttpException
{
    public function __construct(?string $message = '', \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct(Response::HTTP_FAILED_DEPENDENCY, $message, $previous, $headers, $code);
    }
}
