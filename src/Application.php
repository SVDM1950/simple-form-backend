<?php

namespace App;

use App\Exception\StopException;
use App\Routing\Router;
use Exception;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function file_exists;
use function is_dir;
use function is_readable;
use function is_string;

class Application
{
    protected Container $container;

    public function __construct($configPath = null)
    {
        $this->container = new Container();

        $this->container[Config::class] = new Config();

        if (is_string($configPath) && file_exists($configPath) && is_readable($configPath) && is_dir($configPath)) {
            $this->config()->loadConfigurationDirectory($configPath);
        }

        if ($this->config()->has('services')) {
            try {
                $this->registerServices();
            } catch (Exception $exception) {
                $this->handleException($exception);
                throw new StopException();
            }
        }

        if ($this->config()->has('routes')) {
            $this->registerRoutes();
        }
    }

    /**
     * Register services in the container
     * @throws Exception
     */
    protected function registerServices(): void
    {
        foreach ($this->config()->get('services') as $serviceId => $service) {
            $className = $service['class'] ?? $serviceId;
            $serviceId = $service['id'] ?? $className;
            $arguments = $this->replaceConfigParams($service['arguments'] ?? []);

            if (array_key_exists('factory', $service)) {
                $factoryClass = $service['factory'];
                try {
                    $this->container[$serviceId] = (new $factoryClass($this->container))(...$arguments);
                } catch (Exception $exception) {
                    throw new Exception(
                        "Service '$serviceId' could not be created: {$exception->getMessage()}",
                        Response::HTTP_INTERNAL_SERVER_ERROR,
                        $exception
                    );
                }
                continue;
            }

            try {
                $this->container[$serviceId] = new $className(...$arguments);
            } catch (Exception $exception) {
                throw new Exception(
                    "Service '$serviceId' could not be created: {$exception->getMessage()}",
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    $exception
                );
            }
        }
    }

    protected function registerRoutes(): void
    {
        foreach ($this->config()->get('routes') as $route) {
            $this->router()->addRoute(
                $route['method'] ?? 'GET',
                $route['path'],
                $this->replaceConfigParams($route['handlers'])
            );
        }
    }

    /**
     * Replace config parameters in the given array
     */
    protected function replaceConfigParams(array $params): array
    {
        return array_map(function ($value) {
            if (is_string($value) && \preg_match('/^%(.+)%$/', $value, $matches)) {
                return $this->config()->get($matches[1]);
            } elseif (is_string($value) && $value === '@container') {
                return $this->container;
            } elseif (is_string($value) && \preg_match('/^@(.+)$/', $value, $matches)) {
                return $this->container[$this->config()->get("{${$matches[1]}}.class")];
            }

            return $value;
        }, $params);
    }

    /**
     * Dispatch the request, execute the middlewares, send and return the response
     */
    public function run(Request $request = null): Response
    {
        $request = $request ?? Request::createFromGlobals();

        $response = $this->router()->handleRequest($request);

        if (ob_get_length()) {
            @ob_end_clean(); // remove every output, so we have a clean response
        }

        $response->send();

        return $response;
    }

    /**
     * Handle an exception and return a response
     */
    public function handleException(\Throwable $throwable): Response
    {
        $response = new JsonResponse(null, status: Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->headers->set('Allow', 'POST');

        $response = $this->router()->handleCaughtThrowable($throwable, $response);

        if (ob_get_length()) {
            @ob_end_clean(); // remove every output, so we have a clean response
        }

        $response->send();

        return $response;
    }

    /**
     * Get the container
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Get the config class object
     */
    public function config(): Config
    {
        return $this->container[Config::class];
    }

    /**
     * Get the logger class object
     */
    public function logger(): LoggerInterface
    {
        return $this->container[$this->config()->get('services.logger.class')];
    }

    /**
     * Get the router class object
     */
    public function router(): Router
    {
        return $this->container[$this->config()->get('services.router.class')];
    }
}
