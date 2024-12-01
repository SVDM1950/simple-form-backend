<?php

namespace Tests\Unit\Handler\Tickets;

use App\Handler\Tickets\FinishHandler;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Attribute\Incomplete;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\UnitTester;

#[CoversClass(FinishHandler::class)]
#[CoversMethod(FinishHandler::class, '__invoke')]
class FinishHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(FinishHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(FinishHandler::class, '__invoke'));
    }

    #[Incomplete('Response content are always false because handle method is mocked.')]
    public function testInvokeMethod()
    {
        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $handler = new FinishHandler();
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
        $this->assertEquals($response->getContent(), $actualResponse->getContent());
    }
}
