<?php

declare(strict_types=1);

namespace Tests\Unit\Handler\Tickets\School;

use App\Config;
use App\Handler\Exception\ValidationException;
use App\Handler\Tickets\School\ValidationHandler;
use App\Routing\DefaultRoutingHandler;
use App\Validation\Validator;
use App\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for School ValidationHandler with supervisor validation
 */
class ValidationHandlerTest extends TestCase
{
    private ValidationHandler $handler;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = new Container();
        
        // Mock Config
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function ($key) {
            if ($key === 'events') {
                return [
                    'event1' => ['visible' => true],
                    'event2' => ['visible' => false],
                ];
            }
            if ($key === 'tickets') {
                return [
                    'students' => ['school' => true],
                    'supervisors' => ['school' => true],
                    'adult' => ['school' => false],
                ];
            }
            return [];
        });
        
        $this->container[Config::class] = $config;
        
        // Create real validator with our custom rule
        $validatorFactory = new ValidatorFactory($this->container);
        $this->container[Validator::class] = $validatorFactory();
        
        $this->handler = new ValidationHandler();
        $this->handler->setContainer($this->container);
    }

    /**
     * Test that validation passes with valid supervisor count
     */
    public function testValidSupervisorCount(): void
    {
        $requestData = [
            'name' => 'Test School',
            'teacher' => 'John Doe',
            'class' => '5A',
            'email' => 'test@school.com',
            'event' => 'event1',
            'message' => 'This is a test message',
            'tickets' => [
                'students' => 25,
                'supervisors' => 3, // Valid: 25/10 = 2.5, floor = 2, so 3 is valid
            ]
        ];

        $request = new Request([], $requestData);
        $response = new Response();
        $routingHandler = $this->createMock(DefaultRoutingHandler::class);
        $routingHandler->expects($this->once())
                      ->method('handle')
                      ->willReturn($response);

        $result = $this->handler->__invoke($request, $response, $routingHandler);
        
        $this->assertSame($response, $result);
    }

    /**
     * Test that validation fails when supervisors is 0
     */
    public function testSupervisorCountCannotBeZero(): void
    {
        $this->expectException(ValidationException::class);
        
        $requestData = [
            'name' => 'Test School',
            'teacher' => 'John Doe',
            'class' => '5A',
            'email' => 'test@school.com',
            'event' => 'event1',
            'message' => 'This is a test message',
            'tickets' => [
                'students' => 20,
                'supervisors' => 0, // Invalid: cannot be 0
            ]
        ];

        $request = new Request([], $requestData);
        $response = new Response();
        $routingHandler = $this->createMock(DefaultRoutingHandler::class);

        $this->handler->__invoke($request, $response, $routingHandler);
    }

    /**
     * Test that validation fails when supervisor count is too low
     */
    public function testInsufficientSupervisorCount(): void
    {
        $this->expectException(ValidationException::class);
        
        $requestData = [
            'name' => 'Test School',
            'teacher' => 'John Doe',
            'class' => '5A',
            'email' => 'test@school.com',
            'event' => 'event1',
            'message' => 'This is a test message',
            'tickets' => [
                'students' => 25,
                'supervisors' => 1, // Invalid: 25/10 = 2.5, floor = 2, so need at least 2
            ]
        ];

        $request = new Request([], $requestData);
        $response = new Response();
        $routingHandler = $this->createMock(DefaultRoutingHandler::class);

        $this->handler->__invoke($request, $response, $routingHandler);
    }

    /**
     * Test edge case with exactly 10 students
     */
    public function testExactlyTenStudents(): void
    {
        $requestData = [
            'name' => 'Test School',
            'teacher' => 'John Doe',
            'class' => '5A',
            'email' => 'test@school.com',
            'event' => 'event1',
            'message' => 'This is a test message',
            'tickets' => [
                'students' => 10,
                'supervisors' => 1, // Valid: 10/10 = 1, floor = 1, so 1 is valid
            ]
        ];

        $request = new Request([], $requestData);
        $response = new Response();
        $routingHandler = $this->createMock(DefaultRoutingHandler::class);
        $routingHandler->expects($this->once())
                      ->method('handle')
                      ->willReturn($response);

        $result = $this->handler->__invoke($request, $response, $routingHandler);
        
        $this->assertSame($response, $result);
    }
}