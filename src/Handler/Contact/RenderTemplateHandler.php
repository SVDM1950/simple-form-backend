<?php

namespace App\Handler\Contact;

use App\Config;
use App\Handler\Exception\TemplateRenderException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use FilterGuard\FilterGuard;
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
            $mustache = $this->mustache();

            $template = $mustache->loadTemplate('contact-form');

            $content = $template->render($this->data($request));

            $response->setData(['message' => $content]);
        } catch (Mustache_Exception $exception) {
            throw new TemplateRenderException(
                ["Template render error: {$exception->getMessage()}"],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $exception
            );
        }

        return $handler->handle($request, $response);
    }

    protected function data(Request $request): object {
        return (object)[
            'name' => FilterGuard::sanitizeString($request->get('name')),
            'subject' => FilterGuard::sanitizeString($request->get('subject')),
            'message' => FilterGuard::sanitizeString($request->get('message')),
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
}
