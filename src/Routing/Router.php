<?php

namespace App\Routing;

use App\Config;
use App\Handler\Exception\ValidationException;
use App\InternalError\AbstractInternalError;
use App\Routing\Exception\MethodNotAllowedException;
use App\Routing\Exception\RequestHandlerException;
use App\Routing\Exception\RouteNotFoundException;
use App\Routing\Exception\RoutingException;
use FastRoute\DataGenerator\GroupCountBased as DefaultDataGenerator;
use FastRoute\Dispatcher as DispatcherInterface;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as DefaultRouteParser;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Router class
 */
class Router extends RouteCollector
{
    /**
     * Router constructor.
     */
    public function __construct(protected Container $container)
    {
        $parser = $this->config()->get('router.route_parser', DefaultRouteParser::class);
        $dataGenerator = $this->config()->get('router.data_generator', DefaultDataGenerator::class);

        if (is_string($parser)) {
            $parser = new $parser();
        }

        if (is_string($dataGenerator)) {
            $dataGenerator = new $dataGenerator();
        }

        parent::__construct($parser, $dataGenerator);
    }

    public function addRoute($httpMethod, $route, $handler): void
    {
        if (!is_array($handler)) {
            $handler = [$handler];
        }
        parent::addRoute($httpMethod, $route, $handler);
    }


    /**
     * Handle the request
     */
    public function handleRequest(Request $request): Response
    {
        // Create response with CORS headers
        $responseClass = $this->getResponseClass();
        $allowedMethods = array_unique(array_merge(array_keys($this->getData()[0]), array_keys($this->getData()[0])));
        $response = new $responseClass(null, status: Response::HTTP_ACCEPTED);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', join(", ", $allowedMethods));
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->headers->set('Allow', join(", ", $allowedMethods));

        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return $response->setStatusCode(Response::HTTP_OK);
        }

        try {
            $dispatcherClass = $this->getDispatcherClass();
            $dispatcher = new $dispatcherClass($this->getData());
            $result = $dispatcher->dispatch($request->getMethod(), $request->getBaseUrl() . $request->getPathInfo());
            $response = $this->handleDispatchResult($result, $request, $response);
        } catch (Throwable $exception) {
            $response = $this->handleCaughtThrowable($exception, $response);
        }

        return $response;
    }

    /**
     * Get the config class object
     */
    public function config(): Config
    {
        return $this->container[Config::class];
    }

    public function logger(): LoggerInterface
    {
        return $this->container[$this->config()->get('services.logger.class')];
    }

    /**
     * Get the response class name
     */
    protected function getResponseClass(): string
    {
        return $this->config()->get('router.response', JsonResponse::class);
    }

    /**
     * Get the dispatcher class name
     */
    protected function getDispatcherClass(): string
    {
        return $this->config()->get('router.dispatcher', Dispatcher::class);
    }

    /**
     * Get the handler class name
     */
    protected function getHandlerClass(): string
    {
        return $this->config()->get('router.handler', DefaultRoutingHandler::class);
    }

    /**
     * Handle dispatch result
     *
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     */
    protected function handleDispatchResult(array $dispatchResult, Request $request, Response $response): Response
    {
        $request->attributes->add([
            '_route' => $dispatchResult[1] ?? null,
            '_route_params' => $dispatchResult[2] ?? []
        ]);

        $handlerClass = $this->getHandlerClass();

        return match ($dispatchResult[0]) {
            DispatcherInterface::NOT_FOUND          => throw new RouteNotFoundException(),
            DispatcherInterface::METHOD_NOT_ALLOWED => throw new MethodNotAllowedException(),
            DispatcherInterface::FOUND              => (new $handlerClass($dispatchResult[1], $this->container))
                ->handle($request, $response)
        };
    }

    /**
     * Handle caught throwable
     */
    public function handleCaughtThrowable(Throwable $throwable, Response $response): Response
    {
        $throwable = ($throwable instanceof RoutingException) ? new RouteNotFoundException($throwable) : $throwable;
        $this->logger()->error($this->throwableToString($throwable));

        return match (true) {
            $throwable instanceof RoutingException        => $this->createRoutingExceptionResponse($throwable, $response),
            $throwable instanceof ValidationException     => $this->createValidationExceptionResponse($throwable, $response),
            $throwable instanceof RequestHandlerException => $this->createHandlerExceptionResponse($throwable, $response),
            default                                       => $this->createErrorResponse($throwable, $response),
        };
    }

    /**
     * Create a response for request handler exception
     */
    protected function createRoutingExceptionResponse(RoutingException $exception, Response $response): Response
    {
        $responseClass = $response::class;
        return new $responseClass(
            ['type' => 'general', 'errors' => [$exception->getMessage()]],
            status: $exception->getCode(),
            headers: $response->headers->all(),
        );
    }

    protected function createValidationExceptionResponse(ValidationException $exception, Response $response): Response
    {
        $messages = [];
        foreach ($exception->getErrors() as $key => $errors) {
            $messages[$key] = [];
            foreach (array_keys($errors) as $error) {
                $messages[$key][] = match ($error) {
                    'required'            => 'required',
                    'min'                 => 'min',
                    'email', 'in'         => 'invalid',
                    'integer'             => 'integer',
                    'at_least_one_ticket' => 'least',
                    default               => throw new \RuntimeException("Unknown error: $error"),
                };
            }

            if (count($messages[$key]) >= 1) {
                $messages[$key] = array_shift($messages[$key]);
            }
        }

        $responseClass = $response::class;
        return new $responseClass(
            ['type' => 'validation', 'errors' => $messages],
            status: $exception->getCode(),
            headers: $response->headers->all(),
        );


    }

    /**
     * Create a response for request handler exception
     */
    protected function createHandlerExceptionResponse(RequestHandlerException $exception, Response $response): Response
    {
        $responseClass = $response::class;
        return new $responseClass(
            ['type' => $exception->getType(), 'errors' => $exception->getErrors()],
            status: Response::HTTP_BAD_REQUEST,
            headers: $response->headers->all(),
        );
    }

    /**
     * Create an error response
     */
    protected function createErrorResponse(Throwable $exception, Response $response): Response
    {
        $responseClass = $response::class;
        return new $responseClass(
            ['type' => 'general', 'errors' => [$this->throwableToString($exception)]],
            status: $exception instanceof AbstractInternalError ? $exception->getResponseCode() : 500,
            headers: $response->headers->all(),
        );
    }

    /**
     * Convert throwable to string
     */
    public function throwableToString(Throwable $throwable): string
    {
        $msg = $throwable->getMessage();

        if (empty($msg)) {
            $msg = sprintf('[%s]', get_class($throwable));
        }

        if ($throwable instanceof AbstractInternalError) {
            return $msg;
        }

        return sprintf(
            '%s in %s:%d',
            $msg,
            $throwable->getFile(),
            $throwable->getLine()
        );
    }
}
