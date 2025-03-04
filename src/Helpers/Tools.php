<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Helpers;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class Tools
{
    public static function filterErrorMessage(string $message): string
    {
        // hide token parameters
        return preg_replace('/([&?]token=)[\w\d-]+/i', '${1}hidden', $message);
    }

    public static function createLoggerMiddleware(LoggerInterface $logger): callable
    {
        return Middleware::log(
            $logger,
            new MessageFormatter('[{method}] {uri}: CODE={code}, ERROR={error}, CACHE={res_header_X-Kevinrob-Cache}')
        );
    }
}
