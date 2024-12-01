<?php

namespace Tests\Unit\Handler;

use App\Handler\EmptyBodyRequestHandler;
use App\Handler\Exception\EmptyBodyRequestException;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(EmptyBodyRequestHandler::class)]
#[CoversMethod(EmptyBodyRequestHandler::class, '__invoke')]
class EmptyBodyRequestHandlerTest extends Unit
{
    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(EmptyBodyRequestHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(EmptyBodyRequestHandler::class, '__invoke'));
    }

    /**
     * @throws Exception
     */
    public function testRequestWithEmptyBodyWillThrowException()
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('');

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $this->expectException(EmptyBodyRequestException::class);

        $handler = new EmptyBodyRequestHandler();
        $handler($request, $response, $routingHandler);
    }

    /**
     * @throws Exception
     * @throws EmptyBodyRequestException
     */
    public function testRequestWithNonEmptyBodyPasses()
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"key": "value"}');

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $handler = new EmptyBodyRequestHandler();
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
    }
}
