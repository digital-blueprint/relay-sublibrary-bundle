<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\ApiPlatform;

use DateTime;
use Dbp\Relay\SublibraryBundle\Helpers\ItemNotStoredException;
use Dbp\Relay\SublibraryBundle\Helpers\Tools;
use Dbp\Relay\SublibraryBundle\Service\AlmaApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class PostBookLoanByBookOffer extends AbstractController
{
    /** @var AlmaApi */
    protected $api;

    public function __construct(AlmaApi $api)
    {
        $this->api = $api;
    }

    public function __invoke(string $identifier, Request $request): BookLoan
    {
        $this->api->checkPermissions();

        $bookOffer = $this->api->getBookOffer($identifier);
        $bodyData = $this->decodeRequest($request);
        $bookLoan = $this->api->createBookLoan($bookOffer, $bodyData);

        // the Alma API doesn't support setting the end time when creating a book loan,
        // so we need to update it afterwards
        if (isset($bodyData['endTime'])) {
            try {
                $endTime = new DateTime($bodyData['endTime']);
                $bookLoan->setEndTime($endTime);
                $this->api->updateBookLoan($bookLoan);
            } catch (\Exception $e) {
            }
        }

        return $bookLoan;
    }

    /**
     * @return mixed
     *
     * @throws ItemNotStoredException
     */
    protected function decodeRequest(Request $request)
    {
        $content = (string) $request->getContent();
        try {
            return Tools::decodeJSON($content, true);
        } catch (\JsonException $e) {
            throw new ItemNotStoredException(sprintf('Invalid json: %s', Tools::filterErrorMessage($e->getMessage())));
        }
    }
}
