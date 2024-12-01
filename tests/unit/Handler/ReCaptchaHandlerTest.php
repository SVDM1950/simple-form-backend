<?php

namespace Tests\Unit\Handler;

use App\Config;
use App\Handler\Exception\RecaptchaException;
use App\Handler\Exception\ValidationException;
use App\Handler\ReCaptchaHandler;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response as ReCaptchaResponse;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\UnitTester;

#[CoversClass(ReCaptchaHandler::class)]
#[CoversMethod(ReCaptchaHandler::class, '__invoke')]
#[CoversMethod(ReCaptchaHandler::class, 'validate')]
#[CoversMethod(ReCaptchaHandler::class, 'verify')]
class ReCaptchaHandlerTest extends Unit
{
    protected UnitTester $tester;

    #[Test]
    public function implementRequestHandlerInterface()
    {
        $interfaces = class_implements(ReCaptchaHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    #[Test]
    public function hasInvokeMethod()
    {
        $this->assertTrue(method_exists(ReCaptchaHandler::class, '__invoke'));
    }

    #[Test]
    public function implementContainerAwareInterface()
    {
        $interfaces = class_implements(ReCaptchaHandler::class);
        $this->assertContains(ContainerAware::class, $interfaces);
    }

    #[Test]
    public function hasSetContainerMethod()
    {
        $this->assertTrue(method_exists(ReCaptchaHandler::class, 'setContainer'));
    }

    /**
     * @throws Exception
     * @throws RecaptchaException
     * @throws ReflectionException
     * @throws ValidationException
     */
    #[Test]
    public function withoutSiteKeyDoesNothing()
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag();

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        /** @var MockObject&ReCaptchaHandler $handler */
        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class, ['__invoke']);
        $handler->expects($this->never())->method('validate');
        $handler->expects($this->never())->method('verify');

        $reflection = new \ReflectionClass($handler);
        $reflection->getProperty('section')->setValue($handler, 'contact');

        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
        $this->assertEquals(null, $actualResponse->getContent());
    }

    /**
     * @throws Exception
     * @throws RecaptchaException
     * @throws ReflectionException
     * @throws ValidationException
     */
    #[Test]
    public function withSiteKeyCallsValidateAndVerify()
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag();

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
             ['recaptcha.contact.siteKey', null, 'siteKey'],
             ['recaptcha.contact.enabled', null, true]
        ]);

        /** @var MockObject&ReCaptchaHandler $handler */
        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class, ['__invoke']);
        $handler->method('config')->willReturn($config);
        $handler->expects($this->once())->method('validate');
        $handler->expects($this->once())->method('verify');

        $reflection = new \ReflectionClass($handler);
        $reflection->getProperty('section')->setValue($handler, 'contact');

        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertEquals(null, $actualResponse->getContent());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function validateWithInvalidDataWillThrowException()
    {
        $parameterName = 'g-recaptcha-response';

        $request = $this->createMock(Request::class);
        $request->request = new InputBag();
        $request->request->set('key', 'value');

        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class);
        $handler->method('getParameterName')->willReturn($parameterName);

        $this->expectException(ValidationException::class);

        $reflectionClass = new ReflectionClass(ReCaptchaHandler::class);
        $validate = $reflectionClass->getMethod('validate');
        $validate->invoke($handler, $request);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function validateWithValidDataPasses()
    {
        $parameterName = 'g-recaptcha-response';

        $request = $this->createMock(Request::class);
        $request->request = new InputBag();
        $request->request->set($parameterName, 'value');

        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class);
        $handler->method('getParameterName')->willReturn($parameterName);

        $reflectionClass = new ReflectionClass(ReCaptchaHandler::class);
        $validate = $reflectionClass->getMethod('validate');
        $validate->invoke($handler, $request);

        $this->assertTrue(true);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    #[Test]
    public function verifyWithInvalidDataWillThrowException()
    {
        $parameterName = 'g-recaptcha-response';

        $request = $this->createMock(Request::class);
        $request->request = new InputBag();
        $request->request->set($parameterName, 'value');

        $response = $this->createMock(ReCaptchaResponse::class);
        $response->method('isSuccess')->willReturn(false);
        $response->method('getErrorCodes')->willReturn(['error']);

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $reCaptcha->method('verify')->willReturn($response);

        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class);
        $handler->method('getParameterName')->willReturn($parameterName);
        $handler->method('reCaptcha')->willReturn($reCaptcha);

        $this->expectException(RecaptchaException::class);

        $reflectionClass = new ReflectionClass(ReCaptchaHandler::class);
        $verify = $reflectionClass->getMethod('verify');
        $verify->invoke($handler, $request);
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    #[Test]
    public function verifyWithValidDataPasses()
    {
        $parameterName = 'g-recaptcha-response';

        $request = $this->createMock(Request::class);
        $request->request = new InputBag();
        $request->request->set($parameterName, 'value');

        $response = $this->createMock(ReCaptchaResponse::class);
        $response->method('isSuccess')->willReturn(true);

        $reCaptcha = $this->createMock(ReCaptcha::class);
        $reCaptcha->method('verify')->willReturn($response);

        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class);
        $handler->method('getParameterName')->willReturn($parameterName);
        $handler->method('reCaptcha')->willReturn($reCaptcha);

        $reflectionClass = new ReflectionClass(ReCaptchaHandler::class);
        $verify = $reflectionClass->getMethod('verify');
        $verify->invoke($handler, $request);

        $this->assertTrue(true);
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
