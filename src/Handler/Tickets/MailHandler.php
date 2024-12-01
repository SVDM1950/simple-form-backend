<?php

namespace App\Handler\Tickets;

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
            $mailer->setFrom($this->config()->get('mail.tickets.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.tickets.from.name')));
            $mailer->addBCC($this->config()->get('mail.tickets.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.tickets.from.name')));
            $mailer->addAddress($request->get('email'), FilterGuard::sanitizeString($request->get('name')));

            // Sending plain text email
            $mailer->isHTML(false); // Set email format to plain text
            $mailer->Subject = FilterGuard::sanitizeString($this->config()->get('mail.tickets.subject'))." [{$content['id']}]";
            $mailer->Body = $content['message'];

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
        return $this->container['phpmailer.tickets'];
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }
}
