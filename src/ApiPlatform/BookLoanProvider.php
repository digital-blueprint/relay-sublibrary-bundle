<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Dbp\Relay\CoreBundle\Exception\ApiError;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\Pagination;
use Dbp\Relay\CoreBundle\Rest\Query\Pagination\WholeResultPaginator;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Component\HttpFoundation\Response;

final class BookLoanProvider implements ProviderInterface
{
    /** @var AlmaApi */
    private $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return WholeResultPaginator|BookLoan
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

        // TODO: do pagination via API
        return new WholeResultPaginator($bookLoans->toArray(),
            Pagination::getCurrentPageNumber($filters),
            Pagination::getMaxNumItemsPerPage($filters));
    }
}
