<?php

namespace App\Handler\Tickets;

use App\Config;
use App\Helper\ArrayUtils;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use DateTime;
use FilterGuard\FilterGuard;
use Godruoyi\Snowflake\Snowflake;
use Mustache_Engine;
use Mustache_Exception;
use Mustache_Loader_FilesystemLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenderTemplateHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @param JsonResponse $response
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $mustache = new Mustache_Engine(array(
                'entity_flags' => ENT_QUOTES,
                # 'cache' => dirname(__FILE__).'/../var/cache/mustache',
                'loader' => new Mustache_Loader_FilesystemLoader('templates'),
                'partials_loader' => new Mustache_Loader_FilesystemLoader('templates/partials'),
                # 'logger' => $this->logger(),
                'charset' => 'UTF-8',
                'strict_callables' => true,
                'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
                'helpers' => [
                    'date' => fn(string $date) => (new DateTime($date))->format('d.m.Y H:i'),
                    'time' => fn(string $date) => (new DateTime($date))->format('H:i'),
                    'datetime' => fn(string $date) => (new DateTime($date))->format('d.m.Y H:i'),
                    'week' => fn(string $date) => (new DateTime($date . ' - 1 week'))->format('d.m.Y H:i'),
                    'currency' => fn(string $value) => number_format((float) $value, 2, ',', '.'),
                ]
            ));
            $template = $mustache->loadTemplate('tickets-form');

            $orderId = $this->snowflake()->id();

            $events = $this->config()->get('events');

            $tickets = array_values(ArrayUtils::map(
                callback: function ($key, $value) use ($request) {
                    $value['amount'] = FilterGuard::sanitizeString($request->get('tickets')[$key]);
                    return $value;
                },
                array: $this->config()->get('tickets')
            ));

            $total = array_reduce($tickets, fn($total, $ticket) => $total + ($ticket['amount'] * $ticket['price']));

            $content = $template->render((object)[
                'name' => FilterGuard::sanitizeString($request->get('name')),
                'event' => $events[FilterGuard::sanitizeString($request->get('event'))],
                'message' => FilterGuard::sanitizeString($request->get('message')),
                'tickets' => $tickets,
                'bestellnummer' => $orderId,
                'total' => $total,
            ]);

            $response->setData(['id' => $orderId, 'message' => $content]);
        } catch (Mustache_Exception $exception) {
//            $this->logger()->error("Template render error: {$exception->getMessage()}");
            throw new \RuntimeException("Template render error: {$exception->getMessage()}");

//            return $response
//                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
//                ->setData([
//                    'type' => 'general',
//                    'errors' => ['Template render error']
//                ]);
        }

        return $handler->handle($request, $response);
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }

    protected function snowflake(): Snowflake
    {
        return $this->container[Snowflake::class];
    }
}
