<?php

declare(strict_types=1);

namespace DBP\API\AlmaBundle\Helpers;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItemNotFoundException extends NotFoundHttpException
{
}
