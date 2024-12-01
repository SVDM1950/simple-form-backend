<?php

namespace Tests\Unit\Handler\Contact;

use App\Handler\Contact\ValidationHandler;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\UnitTester;

#[CoversClass(ValidationHandler::class)]
#[CoversMethod(ValidationHandler::class, '__invoke')]
class ValidationHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(ValidationHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(ValidationHandler::class, '__invoke'));
    }

    public function testImplementContainerAwareInterface()
    {
        $interfaces = class_implements(ValidationHandler::class);
        $this->assertContains(ContainerAware::class, $interfaces);
    }

    public function testHasSetContainerMethod()
    {
        $this->assertTrue(method_exists(ValidationHandler::class, 'setContainer'));
    }

    /**
     * @throws Exception
     */
    #[DataProvider('invalidData')]
    public function testInvalidDataWillThrowException($data)
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag($data);

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $this->expectException(ValidationException::class);

        $handler = new ValidationHandler();
        $handler($request, $response, $routingHandler);
    }

    public function testValidDataPasses()
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

        $handler = new ValidationHandler();
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
    }

    protected function invalidData(): array
    {
        return [
            ['data' => []],
            ['data' => [
                'email' => 'john.doe@example.com',
                'subject' => 'Hello World!',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'subject' => 'Hello World!',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'subject' => 'Hello World!',
            ]],
            ['data' => [
                'name' => 'J',
                'email' => 'john.doe@example.com',
                'subject' => 'Hello World!',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe#example.com',
                'subject' => 'Hello World!',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'subject' => 'Hello',
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'subject' => 'Hello World!',
                'message' => 'Hello'
            ]],
        ];
    }
}
