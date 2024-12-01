<?php

namespace App\Handler\Contact;

use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FinishHandler implements RequestHandler
{
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $handler->handle($request, $response);
    }
}
