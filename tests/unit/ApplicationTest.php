<?php

namespace Tests\Unit;

use App\Config;
use App\Application;
use Codeception\Attribute\Before;
use Codeception\Attribute\Skip;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(Application::class)]
#[CoversMethod(Application::class, 'run')]
#[CoversMethod(Application::class, 'config')]
class ApplicationTest extends Unit
{
    protected Application $application;

    #[Before]
    protected function _before(): void
    {
        $this->application = new Application(codecept_data_dir("app/config"));
        $this->application->config()->set('logger.path', codecept_output_dir('application/logs'));
        $this->application->config()->set('logger.level', LogLevel::DEBUG);
    }

    #[Test]
    public function configReturnsConfigClassInstance(): void
    {
        $this->assertTrue($this->application->config() instanceof Config);
    }

    #[Test]
    #[Skip]
    public function runApplication(): void
    {
        $json = '{ "name": "N", "email": "E", "subject": "S", "message": "M" }';

        $request = Request::create('https://mail.rollkunslauf.org/mail.php', 'POST', [], [], [], [], $json);

        $this->expectOutputString('');

        $response = $this->application->run($request);
    }
}
