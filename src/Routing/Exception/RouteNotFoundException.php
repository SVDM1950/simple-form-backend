<?php

namespace App\Routing\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RouteNotFoundException extends RoutingException
{
    public function __construct(private readonly ?Throwable $previous = null)
    {
        parent::__construct('Route not found', Response::HTTP_NOT_FOUND, $this->previous);
    }
}
