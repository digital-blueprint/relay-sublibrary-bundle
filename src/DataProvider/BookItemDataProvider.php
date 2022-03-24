<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Dbp\Relay\SublibraryBundle\Entity\Book;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Book::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Book
    {
        $api = $this->api;
        $api->checkPermissions();

        $data = $api->getBookJsonData($id);
        $book = $api->bookFromJsonItem($data);

        return $book;
    }
}
