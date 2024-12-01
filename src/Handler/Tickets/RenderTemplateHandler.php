<?php

namespace App\Handler\Tickets;

use App\Config;
use App\Handler\Exception\TemplateRenderException;
use App\Helper\ArrayUtils;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use FilterGuard\FilterGuard;
use Godruoyi\Snowflake\Snowflake;
use Mustache_Engine;
use Mustache_Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenderTemplateHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @param JsonResponse $response
     * @throws TemplateRenderException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $orderId = $this->snowflake()->id();

            $mustache = $this->mustache();

            $template = $mustache->loadTemplate('tickets-form');

            $content = $template->render($this->data($request, $orderId));

            $response->setData(['id' => $orderId, 'message' => $content]);
        } catch (Mustache_Exception $exception) {
            throw new TemplateRenderException(
                ["Template render error: {$exception->getMessage()}"],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception
            );
        }

        return $handler->handle($request, $response);
    }

    protected function data(Request $request, string $orderId): object
    {
        $events = $this->config()->get('events');

        $tickets = array_values(ArrayUtils::map(
            callback: function ($key, $value) use ($request) {
                $value['amount'] = FilterGuard::sanitizeString($request->get('tickets')[$key]);
                return $value;
            },
            array: $this->config()->get('tickets')
        ));

        $total = array_reduce($tickets, fn($total, $ticket) => $total + ($ticket['amount'] * $ticket['price']));

        return (object)[
            'name' => FilterGuard::sanitizeString($request->get('name')),
            'event' => $events[FilterGuard::sanitizeString($request->get('event'))],
            'message' => FilterGuard::sanitizeString($request->get('message')),
            'tickets' => $tickets,
            'bestellnummer' => $orderId,
            'total' => $total,
        ];
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }

    protected function mustache(): Mustache_Engine
    {
        return $this->container[Mustache_Engine::class];
    }

    protected function snowflake(): Snowflake
    {
        return $this->container[Snowflake::class];
    }
}
