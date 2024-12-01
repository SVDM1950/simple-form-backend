<?php

namespace App\Handler;

use App\Config;
use App\Handler\Exception\MailerException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use FilterGuard\FilterGuard;
use JsonException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MailHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected string $section)
    {
    }

    /**
     * @throws MailerException
     * @throws JsonException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $mailer = $this->mailer();

            $content = json_decode($response->getContent(), flags: JSON_THROW_ON_ERROR|JSON_OBJECT_AS_ARRAY);
            $response->setContent(null);

            // Sender and recipient settings
            $mailer->setFrom($this->config()->get("mail.{$this->section}.from.email"), FilterGuard::sanitizeString($this->config()->get("mail.{$this->section}.from.name")));
            $mailer->addBCC($this->config()->get("mail.{$this->section}.from.email"), FilterGuard::sanitizeString($this->config()->get("mail.{$this->section}.from.name")));
            $mailer->addAddress($request->get('email'), FilterGuard::sanitizeString($request->get('name')));

            // Sending plain text email
            $mailer->isHTML(false); // Set email format to plain text
            $mailer->Subject = FilterGuard::sanitizeString($this->config()->get("mail.{$this->section}.subject"));
            $mailer->Body = $content['message'];

            if (!$mailer->send()) {
                throw new MailerException([$mailer->ErrorInfo]);
            }
        } catch (PHPMailerException $exception) {
            throw new MailerException([$exception->getMessage()], $exception->getCode(), $exception);
        }

        return $handler->handle($request, $response);
    }

    protected function mailer(): PHPMailer
    {
        return $this->container["phpmailer.{$this->section}"];
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }
}
