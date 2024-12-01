<?php

namespace Tests\Unit\Handler;

use App\Config;
use App\Handler\Contact\ReCaptchaHandler;
use App\Handler\Exception\RecaptchaException;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
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

    public function testImplementRequestHandlerInterface()
    {
        $interfaces = class_implements(ReCaptchaHandler::class);
        $this->assertContains(RequestHandler::class, $interfaces);
    }

    public function testHasInvokeMethod()
    {
        $this->assertTrue(method_exists(ReCaptchaHandler::class, '__invoke'));
    }

    public function testImplementContainerAwareInterface()
    {
        $interfaces = class_implements(ReCaptchaHandler::class);
        $this->assertContains(ContainerAware::class, $interfaces);
    }

    public function testHasSetContainerMethod()
    {
        $this->assertTrue(method_exists(ReCaptchaHandler::class, 'setContainer'));
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testWithoutSiteKeyDoesNothing()
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
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertInstanceOf(Response::class, $actualResponse);
        $this->assertEquals(0, $actualResponse->getStatusCode());
        $this->assertEquals($response->getContent(), $actualResponse->getContent());
    }

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function testWithSiteKeyCallsValidateAndVerify()
    {
        $request = $this->createMock(Request::class);
        $request->request = new InputBag();

        $response = $this->createMock(Response::class);

        $routingHandler = $this->createMock(RoutingHandler::class);
        $routingHandler->expects($this->once())->method('handle');

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
             ['recaptcha.siteKey', null, 'siteKey'],
             ['recaptcha.enabled', null, true]
        ]);

        /** @var MockObject&ReCaptchaHandler $handler */
        $handler = $this->createMockWithoutMethods(ReCaptchaHandler::class, ['__invoke']);
        $handler->method('config')->willReturn($config);
        $handler->expects($this->once())->method('validate');
        $handler->expects($this->once())->method('verify');
        $actualResponse = $handler($request, $response, $routingHandler);

        $this->assertEquals($response->getContent(), $actualResponse->getContent());
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testValidateWithInvalidDataWillThrowException()
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
    public function testValidateWithValidDataPasses()
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
    public function testVerifyWithInvalidDataWillThrowException()
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
    public function testVerifyWithValidDataPasses()
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
