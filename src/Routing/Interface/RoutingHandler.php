<?php

namespace App\Routing\Interface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface RoutingHandler
{
    public function handle(Request $request, Response $response): Response;
}
