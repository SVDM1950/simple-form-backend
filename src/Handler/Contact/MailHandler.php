<?php

namespace App\Handler\Contact;

use App\Config;
use App\Handler\Exception\MailerException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use FilterGuard\FilterGuard;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MailHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @throws MailerException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $mailer = $this->mailer();

            $content = $response->getContent();
            $response->setContent(null);

            // Sender and recipient settings
            $mailer->setFrom($this->config()->get('mail.contact.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.contact.from.name')));
            $mailer->addBCC($this->config()->get('mail.contact.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.contact.from.name')));
            $mailer->addAddress($request->get('email'), FilterGuard::sanitizeString($request->get('name')));

            // Sending plain text email
            $mailer->isHTML(false); // Set email format to plain text
            $mailer->Subject = FilterGuard::sanitizeString($this->config()->get('mail.contact.subject'));
            $mailer->Body = $content;

            if (!$mailer->send()) {
                throw new MailerException([$mailer->ErrorInfo]);
//                $this->logger()->error("Message could not be sent. Mailer Error: {$mailer->ErrorInfo}");

//                return $response
//                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
//                    ->setData([
//                        'type' => 'mail',
//                        'errors' => ['Message could not be sent']
//                    ]);
            }
        } catch (PHPMailerException $exception) {
//            $this->logger()->error("Message could not be sent. Mailer Error: {$exception->getMessage()}");

//            return $response
//                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
//                ->setData([
//                    'type' => 'mail',
//                    'errors' => ['Message could not be sent']
//                ]);
            throw new MailerException([$exception->getMessage()], $exception->getCode(), $exception);
        }

        return $handler->handle($request, $response);
    }

    protected function mailer(): PHPMailer
    {
        return $this->container['phpmailer.contact'];
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }
}
