<?php

namespace App\Handler\Tickets\School;

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

    public function __construct(protected bool $school = false)
    {
    }

    /**
     * @param JsonResponse $response
     * @throws TemplateRenderException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $orderId = $this->snowflake()->id();

            $mustache = $this->mustache();

            $template = $mustache->loadTemplate('school-tickets-form');

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

        $tickets = ArrayUtils::map(
            callback: function ($key, $value) use ($request) {
                if (!$value['school']) {
                    return null; // Skip tickets that are not for schools
                }

                unset($value['school']);

                $value['amount'] = FilterGuard::sanitizeString($request->get('tickets')[$key]);
                return $value;
            },
            array: $this->config()->get('tickets')
        );

        $free = min($tickets['supervisors']['amount'], floor($tickets['students']['amount'] / 10));
        $total = $tickets['students']['amount'] * $tickets['students']['price'];
        $total+= ($tickets['supervisors']['amount'] - $free) * $tickets['supervisors']['price'];

        return (object)[
            'name' => FilterGuard::sanitizeString($request->get('name')),
            'teacher' => FilterGuard::sanitizeString($request->get('teacher')),
            'class' => FilterGuard::sanitizeString($request->get('class')),
            'event' => $events[FilterGuard::sanitizeString($request->get('event'))],
            'message' => FilterGuard::sanitizeString($request->get('message')),
            'tickets' => array_values($tickets),
            'bestellnummer' => $orderId,
            'total' => $total,
            'supervisors' => $tickets['supervisors']['amount'],
            'free' => $free,
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
