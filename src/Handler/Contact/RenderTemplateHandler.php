<?php

namespace App\Handler\Contact;

use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use FilterGuard\FilterGuard;
use Mustache_Engine;
use Mustache_Exception;
use Mustache_Loader_FilesystemLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenderTemplateHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

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
            ));
            $template = $mustache->loadTemplate('contact-form');
            $content = $template->render((object) [
                'name' => FilterGuard::sanitizeString($request->get('name')),
                'subject' => FilterGuard::sanitizeString($request->get('subject')),
                'message' => FilterGuard::sanitizeString($request->get('message')),
            ]);
            $response->setContent($content);
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
}
