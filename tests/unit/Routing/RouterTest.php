<?php

namespace Tests\Unit\Routing;

use App\Config;
use App\Routing\DefaultRoutingHandler;
use App\Routing\Router;
use Codeception\Test\Unit;
use FastRoute\DataGenerator\GroupCountBased as DefaultDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteParser\Std as DefaultRouteParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use Pimple\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\Handler\TestHandler;
use Tests\Support\UnitTester;

#[CoversClass(Router::class)]
#[CoversMethod(Router::class, 'handleRequest')]
class RouterTest extends Unit
{
    protected UnitTester $tester;

    public function testHasHandleRequestMethod()
    {
        $this->assertTrue(method_exists(Router::class, 'handleRequest'));
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestWithoutRoutesWillReturnErrorResponse()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_GET);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(false);
        $request->method('getPathInfo')->willReturn('/test');

        $router = new Router($container);

        $response = $router->handleRequest($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('{"type":"general","errors":["Route not found"]}', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestWithInvalidRequestHandlerWillReturnErrorResponse()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_GET);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(false);
        $request->method('getPathInfo')->willReturn('/test');

        $router = new Router($container);

        $router->addRoute('GET', '/test', function($request, $response, $handler) {
            return $handler->handle($request, $response);
        });

        $response = $router->handleRequest($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('{"type":"general","errors":["Route not found"]}', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestWithRouteNotFoundWillReturnErrorResponse()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_GET);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(false);
        $request->method('getPathInfo')->willReturn('/not-found');

        $router = new Router($container);

        $router->addRoute('GET', '/test', new TestHandler());

        $response = $router->handleRequest($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('{"type":"general","errors":["Route not found"]}', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestWithMethodNotAllowedWillReturnErrorResponse()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_POST);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(false);
        $request->method('getPathInfo')->willReturn('/test');

        $router = new Router($container);

        $router->addRoute('GET', '/test', new TestHandler());

        $response = $router->handleRequest($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('{"type":"general","errors":["Route not found"]}', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestPasses()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_GET);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(false);
        $request->method('getPathInfo')->willReturn('/test');

        $router = new Router($container);

        $router->addRoute('GET', '/test', new TestHandler());

        $response = $router->handleRequest($request);

        $this->assertEquals('["Hello World!"]', $response->getContent());
    }

    /**
     * @throws Exception
     */
    public function testHandleRequestHandlesOptionsHttpMethod()
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap(
            [
                ['router.route_parser', DefaultRouteParser::class, DefaultRouteParser::class],
                ['router.data_generator', DefaultDataGenerator::class, DefaultDataGenerator::class],
                ['router.response', JsonResponse::class, JsonResponse::class],
                ['router.dispatcher', Dispatcher::class, Dispatcher::class],
                ['router.handler', DefaultRoutingHandler::class, DefaultRoutingHandler::class],
            ]
        );

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->with(Config::class)->willReturn($config);

        $request = $this->createMock(Request::class);
        $request->attributes = new ParameterBag();
        $request->method('getMethod')->willReturn(Request::METHOD_OPTIONS);
        $request->method('isMethod')->with(Request::METHOD_OPTIONS)->willReturn(true);
        $request->method('getPathInfo')->willReturn('/test');

        $router = new Router($container);

        $response = $router->handleRequest($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('{}', $response->getContent());
    }
}
