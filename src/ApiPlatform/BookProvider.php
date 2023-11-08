<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;

final class BookProvider implements ProviderInterface
{
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return Book|iterable<Book>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $api = $this->api;
        $api->checkPermissions();

        if ($operation instanceof CollectionOperationInterface) {
            return [];
        } else {
            $id = $uriVariables['identifier'];
            assert(is_string($id));
            $data = $api->getBookJsonData($id);
            $book = $api->bookFromJsonItem($data);

            return $book;
        }
    }
}
