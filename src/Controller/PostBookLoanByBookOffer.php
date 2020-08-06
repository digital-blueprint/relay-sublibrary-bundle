<?php

namespace DBP\API\AlmaBundle\Controller;

use DateTime;
use DBP\API\AlmaBundle\Entity\BookLoan;
use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;
use DBP\API\CoreBundle\Exception\ItemNotUsableException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PostBookLoanByBookOffer.
 *
 * We need to set the annotation `"defaults":{"_api_persist"=false}` in class BookOffer to prevent that the BookOffer
 * will be updated after our controller is done
 */
class PostBookLoanByBookOffer extends AlmaController
{
    /**
     * @throws ItemNotStoredException
     * @throws ItemNotLoadedException
     * @throws ItemNotUsableException
     */
    public function __invoke(BookOffer $data, Request $request = null): BookLoan
    {
        $bodyData = $this->decodeRequest($request);
        $bookLoan = $this->api->createBookLoan($data, $bodyData);

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
