<?php

declare(strict_types=1);

namespace Dbp\Relay\SublibraryBundle\Helpers;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemNotFoundException extends NotFoundHttpException
{
}
