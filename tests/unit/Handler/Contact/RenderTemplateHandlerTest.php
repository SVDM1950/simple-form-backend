<?php

namespace Tests\Unit\Handler\Contact;

use App\Handler\Contact\RenderTemplateHandler;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Attribute\Incomplete;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\UnitTester;

#[CoversClass(RenderTemplateHandler::class)]
#[CoversMethod(RenderTemplateHandler::class, '__invoke')]
class RenderTemplateHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(RenderTemplateHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(RenderTemplateHandler::class, '__invoke'));
    }

    public function testImplementContainerAwareInterface()
    {
        $interfaces = class_implements(RenderTemplateHandler::class);
        $this->assertContains(ContainerAware::class, $interfaces);
    }

    public function testHasSetContainerMethod()
    {
        $this->assertTrue(method_exists(RenderTemplateHandler::class, 'setContainer'));
    }

    #[Incomplete('This test does not throw an exception.')]
    public function testWillThrowException()
    {
        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $this->expectException(\Exception::class);

        $handler = new RenderTemplateHandler();
        $handler($request, $response, $routingHandler);
    }

    #[Incomplete('Response content are always false because handle method is mocked.')]
    public function testPasses()
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Hello World!',
            'message' => 'Hello World!'
        ]);

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $handler = new RenderTemplateHandler();
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
        $this->assertEquals($response->getContent(), $actualResponse->getContent());
    }
}
