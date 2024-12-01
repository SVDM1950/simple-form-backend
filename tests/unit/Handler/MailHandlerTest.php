<?php

namespace Tests\Unit\Handler;

use App\Config;
use App\Handler\Exception\MailerException;
use App\Handler\MailHandler;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Test\Unit;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\UnitTester;

#[CoversClass(MailHandler::class)]
#[CoversMethod(MailHandler::class, '__invoke')]
class MailHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(MailHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(MailHandler::class, '__invoke'));
    }

    public function testImplementContainerAwareInterface()
    {
        $interfaces = class_implements(MailHandler::class);
        $this->assertContains(ContainerAware::class, $interfaces);
    }

    public function testHasSetContainerMethod()
    {
        $this->assertTrue(method_exists(MailHandler::class, 'setContainer'));
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     * @throws \JsonException
     */
    public function testPHPMailerWillThrowException()
    {
        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);
        $response->method('getContent')->willReturn('{"message":"Test-Nachricht"}');

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $config = $this->createMock(Config::class);

        $phpMailer = $this->createMock(PHPMailer::class);
        $phpMailer->method('setFrom')->willThrowException(new PHPMailerException('Invalid address: test@example.com'));

        $this->expectException(MailerException::class);

        /** @var MockObject&MailHandler $handler */
        $handler = $this->createMockWithoutMethods(MailHandler::class, ['__invoke']);
        $handler->method('config')->willReturn($config);
        $handler->method('mailer')->willReturn($phpMailer);

        $reflection = new \ReflectionClass($handler);
        $reflection->getProperty('section')->setValue($handler, 'contact');

        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertEquals(null, $actualResponse->getContent());
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     * @throws \JsonException
     */
    public function testPHPMailerCanNotSendMail()
    {
        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);
        $response->method('getContent')->willReturn('{"message":"Test-Nachricht"}');

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->never())->method('handle');

        $config = $this->createMock(Config::class);

        $phpMailer = $this->createMock(PHPMailer::class);
        $phpMailer->method('send')->willReturn(false);

        $this->expectException(MailerException::class);

        /** @var MockObject&MailHandler $handler */
        $handler = $this->createMockWithoutMethods(MailHandler::class, ['__invoke']);
        $handler->method('config')->willReturn($config);
        $handler->method('mailer')->willReturn($phpMailer);

        $reflection = new \ReflectionClass($handler);
        $reflection->getProperty('section')->setValue($handler, 'contact');

        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertEquals(null, $actualResponse->getContent());
    }

    /**
     * @throws MailerException
     * @throws Exception
     * @throws ReflectionException
     * @throws \JsonException
     */
    public function testPHPMailerCanSendMail()
    {
        $request = $this->createMock(Request::class);

        $response = $this->createMock(Response::class);
        $response->method('getContent')->willReturn('{"message":"Test-Nachricht"}');

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $config = $this->createMock(Config::class);

        $phpMailer = $this->createMock(PHPMailer::class);
        $phpMailer->method('send')->willReturn(true);

        /** @var MockObject&MailHandler $handler */
        $handler = $this->createMockWithoutMethods(MailHandler::class, ['__invoke']);
        $handler->method('config')->willReturn($config);
        $handler->method('mailer')->willReturn($phpMailer);

        $reflection = new \ReflectionClass($handler);
        $reflection->getProperty('section')->setValue($handler, 'contact');

        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
        $this->assertEquals(null, $actualResponse->getContent());
    }

    /**
     * @throws ReflectionException
     */
    protected function createMockWithoutMethods(string $className, array $methods = []): MockObject
    {
        // get method names from reflection
        $class = new ReflectionClass($className);
        $allMethods = array_map(function ($method) {
            return $method->getName();
        }, $class->getMethods());

        // remove methods that should not be mocked
        $methodsToMock = array_diff($allMethods, $methods);

        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods($methodsToMock)
            ->getMock();
    }
}
