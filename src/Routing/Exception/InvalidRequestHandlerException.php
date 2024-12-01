<?php

namespace App\Routing\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InvalidRequestHandlerException extends RoutingException
{
    public function __construct(private readonly ?Throwable $previous = null)
    {
        parent::__construct('Invalid Request Handler', Response::HTTP_INTERNAL_SERVER_ERROR, $this->previous);
    }
}
