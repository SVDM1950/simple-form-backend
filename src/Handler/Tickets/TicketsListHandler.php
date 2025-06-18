<?php

namespace App\Handler\Tickets;

use App\Config;
use App\Helper\ArrayUtils;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TicketsListHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected bool $school = false)
    {
    }

    /**
     * @param JsonResponse $response
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        $response->setData(ArrayUtils::map(
            function($key, $value) {
                if ($value['school'] !== $this->school) {
                    return null; // Skip tickets that are not for this school mode
                }

                unset($value['school']);

                $value['id'] = $key;
                return $value;
            },
            $this->config()->get('tickets')
        ));
        $response->setStatusCode(Response::HTTP_OK);

        return $handler->handle($request, $response);
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }
}
