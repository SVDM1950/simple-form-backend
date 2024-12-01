<?php

namespace App\Routing\Interface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface RequestHandler
{
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response;
}
