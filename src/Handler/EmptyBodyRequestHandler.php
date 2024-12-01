<?php

namespace App\Handler;

use App\Handler\Exception\EmptyBodyRequestException;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmptyBodyRequestHandler implements RequestHandler
{
    /**
     * @throws EmptyBodyRequestException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        if (trim($request->getContent()) === '') {
            throw new EmptyBodyRequestException(['Empty body request']);
        }

        return $handler->handle($request, $response);
    }

}
