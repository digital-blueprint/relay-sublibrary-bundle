<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use DBP\API\AlmaBundle\Entity\Book;
use DBP\API\AlmaBundle\Service\AlmaApi;

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

    /**
     * @throws \DBP\API\CoreBundle\Exception\ItemNotLoadedException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Book
    {
        $api = $this->api;
        $api->checkPermissions();

        $data = $api->getBookJsonData($id);
        $book = $api->bookFromJsonItem($data);

        return $book;
    }
}
