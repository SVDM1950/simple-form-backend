<?php

namespace Tests\Support\Handler;

use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestHandler implements RequestHandler
{
    /**
     * @param Request $request
     * @param JsonResponse $response
     * @param RoutingHandler $handler
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        $response->setData((array) 'Hello World!');
        $response->setStatusCode(Response::HTTP_OK);

        return $handler->handle($request, $response);
    }
}
