<?php

namespace Tests\Unit;

use App\Config;
use App\MailApplication;
use Codeception\Attribute\Before;
use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(MailApplication::class)]
#[CoversMethod(MailApplication::class, 'run')]
#[CoversMethod(MailApplication::class, 'config')]
class MailApplicationTest extends Unit
{
    protected MailApplication $application;

    #[Before]
    protected function _before(): void
    {
        $this->application = new MailApplication();
        $this->application->config()->set('logger.path', codecept_output_dir('application/logs'));
        $this->application->config()->set('logger.level', LogLevel::DEBUG);
    }

    #[Test]
    public function configReturnsConfigClassInstance(): void
    {
        $this->assertTrue($this->application->config() instanceof Config);
    }

    #[Test]
    public function runApplication(): void
    {
        $json = '{ "name": "N", "email": "E", "subject": "S", "message": "M" }';

        $request = Request::create('https://mail.rollkunslauf.org/mail.php', 'POST', [], [], [], [], $json);

        $this->expectOutputString('');

        $response = $this->application->run($request);
    }
}
