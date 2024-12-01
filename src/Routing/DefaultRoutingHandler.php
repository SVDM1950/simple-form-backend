<?php

namespace App\Routing;

use App\Routing\Exception\InvalidRequestHandlerException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultRoutingHandler implements RoutingHandler
{
    public function __construct(
        protected array     $stack,
        protected Container $container
    )
    {
    }

    public function handle(Request $request, Response $response): Response
    {
        if (count($this->stack) === 0) {
            return $response;
        }

        return call_user_func_array($this->shiftStack(), [
            $request,
            $response,
            $this
        ]);
    }

    protected function shiftStack(): callable
    {
        $item = array_shift($this->stack);

        if (is_string($item)) {
            $item = new $item();
        }

        if (!$item instanceof RequestHandler) {
            throw new InvalidRequestHandlerException();
        }

        if ($item instanceof ContainerAware) {
            $item->setContainer($this->container);
        }

        return $item;
    }
}
