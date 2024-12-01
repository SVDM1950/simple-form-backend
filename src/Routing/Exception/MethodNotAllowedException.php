<?php

namespace App\Routing\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MethodNotAllowedException extends RoutingException
{
    public function __construct(private readonly ?Throwable $previous = null)
    {
        parent::__construct('Method not allowed', Response::HTTP_METHOD_NOT_ALLOWED, $this->previous);
    }
}
