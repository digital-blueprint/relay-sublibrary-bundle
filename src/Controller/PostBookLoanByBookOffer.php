<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DateTime;
use DBP\API\AlmaBundle\Entity\BookLoan;
use Symfony\Component\HttpFoundation\Request;

class PostBookLoanByBookOffer extends AlmaController
{
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
}
