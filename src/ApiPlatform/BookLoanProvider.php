<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Helpers\ArrayFullPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Response;

final class BookLoanProvider implements ProviderInterface
{
    public const ITEMS_PER_PAGE = 100;

    /** @var AlmaApi */
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return ArrayFullPaginator|BookLoan
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->api->checkPermissions();

        if (!$operation instanceof CollectionOperationInterface) {
            $id = $uriVariables['identifier'];
            assert(is_string($id));

            $data = $this->api->getBookLoanJsonData($id);
            $bookLoan = $this->api->bookLoanFromJsonItem($data);
            $bookOffer = $bookLoan->getObject();

            // check for the user's permissions to the book offer of the requested book loan
            $this->api->checkCurrentPersonBookOfferPermissions($bookOffer);

            return $bookLoan;
        }

        $filters = $context['filters'] ?? [];

        try {
            $bookLoans = $this->api->getBookLoans($filters);
        } catch (\Exception $e) {
            throw new ApiError(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        $perPage = self::ITEMS_PER_PAGE;
        $page = 1;
        if (isset($filters['page'])) {
            $page = (int) $filters['page'];
        }

        if (isset($filters['perPage'])) {
            $perPage = (int) $filters['perPage'];
        }

        // TODO: do pagination via API
        return new ArrayFullPaginator($bookLoans, $page, $perPage);
    }
}
