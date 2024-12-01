<?php

namespace Tests\Unit\Handler;

use App\Handler\Exception\InvalidJsonDataException;
use App\Handler\JsonRequestHandler;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(JsonRequestHandler::class)]
#[CoversMethod(JsonRequestHandler::class, '__invoke')]
class JsonRequestHandlerTest extends Unit
{
    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(JsonRequestHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(JsonRequestHandler::class, '__invoke'));
    }

    /**
     * @throws Exception
     */
    public function testInvalidJsonWillThrowException()
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"key": "value"');

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $this->expectException(InvalidJsonDataException::class);

        $handler = new JsonRequestHandler();
        $handler($request, $response, $routingHandler);
    }

    /**
     * @throws InvalidJsonDataException
     * @throws Exception
     */
    public function testValidJsonPasses()
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"key": "value"}');
        $request->request = new InputBag();

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $handler = new JsonRequestHandler();
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
    }
}
