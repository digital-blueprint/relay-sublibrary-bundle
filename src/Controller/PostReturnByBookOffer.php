<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Controller;

use DBP\API\AlmaBundle\Entity\BookOffer;
use DBP\API\CoreBundle\Exception\ItemNotLoadedException;
use DBP\API\CoreBundle\Exception\ItemNotStoredException;

/**
 * Class PostReturnByBookOffer.
 *
 * We need to set the annotation `"defaults":{"_api_persist"=false}` in class BookOffer to prevent that the BookOffer
 * will be updated after our controller is done
 */
class PostReturnByBookOffer extends AlmaController
{
    /**
     * @throws ItemNotStoredException
     * @throws ItemNotLoadedException
     */
    public function __invoke(BookOffer $data): BookOffer
    {
        $this->api->returnBookOffer($data);

        // returnBookOffer doesn't return anything so we just return the book offer we got instead
        return $data;
    }
}
