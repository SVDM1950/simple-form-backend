<?php

namespace Tests\Unit\Handler\Tickets;

use App\Config;
use App\Handler\Tickets\ValidationHandler;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Validation\ValidatorFactory;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Pimple\Container;
use Rakit\Validation\RuleNotFoundException;
use Rakit\Validation\RuleQuashException;
use Rakit\Validation\Validator;
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
     * @param $data
     * @throws Exception
     * @throws RuleQuashException
     * @throws ValidationException
     * @throws RuleNotFoundException
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

        $container = $this->getContainer();

        $handler = new ValidationHandler();
        $handler->setContainer($container);
        $handler($request, $response, $routingHandler);
    }

    /**
     * @throws Exception
     * @throws RuleNotFoundException
     * @throws RuleQuashException
     * @throws ValidationException
     */
    public function testValidDataPasses()
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            "event" => "1",
            'message' => 'Hello World!',
            "tickets" => [
                "1" => 1,
                "2" => 0,
                "3" => 0,
            ]
        ]);

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $container = $this->getContainer();

        $handler = new ValidationHandler();
        $handler->setContainer($container);
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
    }

    /**
     * @return Container&MockObject
     * @throws Exception
     * @throws RuleQuashException
     */
    public function getContainer(): Container&MockObject
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
            ['events', null, [
                '1' => [
                    "name" => 'Event 1',
                    "datetime" => '2024-12-08 16:00',
                    "sold" => false,
                    "closed" => false
                ],
                '2' => [
                    "name" => 'Event 2',
                    "datetime" => '2024-12-08 16:30',
                    "sold" => false,
                    "closed" => false
                ],
                '3' => [
                    "name" => 'Event 3',
                    "datetime" => '2024-12-08 17:00',
                    "sold" => false,
                    "closed" => false
                ],
            ]],
            ['tickets', null, [
                '1' => [
                    "name" => 'Ticket 1',
                    "price" => 8.0,
                    "note" => 'Ticket 1 Note'
                ],
                '2' => [
                    "name" => 'Ticket 2',
                    "price" => 12.0,
                    "note" => 'Ticket 2 Note'
                ],
                '3' => [
                    "name" => 'Ticket 3',
                    "price" => 16.0,
                    "note" => 'Ticket 3 Note'
                ],
            ]]
        ]);

        $container = $this->createMock(Container::class);
        $container->method('offsetGet')->willReturnMap([
            [Config::class, $config],
            [Validator::class, (new ValidatorFactory($container))()]
        ]);

        return $container;
    }

    protected function invalidData(): array
    {
        return [
            ['data' => []],
            ['data' => [
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!'
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'J',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe#example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "4",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello',
                "tickets" => [
                    "1" => 1,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
            ['data' => [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                "event" => "1",
                'message' => 'Hello World!',
                "tickets" => [
                    "1" => 0,
                    "2" => 0,
                    "3" => 0,
                ]
            ]],
        ];
    }
}
