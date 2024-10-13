<?php

namespace App;

use Exception;
use FilterGuard\FilterGuard;
use JsonException;
use Katzgrau\KLogger\Logger;
use Mustache_Engine;
use Mustache_Exception;
use Mustache_Loader_FilesystemLoader;
use PhpMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Rakit\Validation\Validator;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MailApplication
{
    protected Config $config;

    protected ?Logger $logger = null;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function run(Request $request = null): Response
    {
        $this->initLogger();

        $request = $request ?? Request::createFromGlobals();

        $response = $this->handle($request);

        if (ob_get_length()) {
            @ob_end_clean(); // remove every output, so we have a clean response
        }

        $response->send();

        return $response;
    }

    public function config(): Config
    {
        return $this->config;
    }

    protected function initLogger(): void
    {
        if ($this->logger) {
            return;
        }

        $this->logger = new Logger($this->config->get('logger.path'), $this->config->get('logger.level'));
    }

    protected function handle(Request $request): Response
    {
        $response = new JsonResponse(null, status: Response::HTTP_ACCEPTED);;
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        $response->headers->set('Allow', 'POST');

        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return $response->setStatusCode(Response::HTTP_OK);
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            $this->logger->error("HTTP method '{$request->getMethod()}' not allowed");

            return $response
                ->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Method not allowed']
                ]);
        }

        try {
            $data = \json_decode((string)$request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            if (\is_array($data)) {
                $request->request->replace($data);
            }
        } catch (JsonException $exception) {
            $this->logger->error("Invalid JSON data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Invalid JSON data']
                ]);
        } catch (Exception $exception) {
            $this->logger->error("Invalid JSON data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Invalid JSON data']
                ]);
        }

        try {
            $this->validate($request);
        } catch (ValidationException $exception) {
            $this->logger->error("Invalid data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setData([
                    'type' => 'validation',
                    'errors' => $this->transformValidationErrors($exception)
                ]);
        } catch (Exception $exception) {
            $this->logger->error("Invalid data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Invalid data']
                ]);
        }

        if ($this->config->get('recaptcha.siteKey')) {
            try {
                $this->validateRecaptcha($request);
            } catch (RecaptchaException $exception) {
                $this->logger->error("Invalid reCAPTCHA data: {$exception->getMessage()}");

                return $response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setData([
                        'type' => 'recaptcha',
                        'errors' => ['Invalid reCAPTCHA data']
                    ]);
            } catch (Exception $exception) {
                $this->logger->error("Invalid reCAPTCHA data: {$exception->getMessage()}");

                return $response
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                    ->setData([
                        'type' => 'recaptcha',
                        'errors' => ['Invalid reCAPTCHA data']
                    ]);
            }
        }

        try {
            $mustache = new Mustache_Engine(array(
                'entity_flags' => ENT_QUOTES,
                # 'cache' => dirname(__FILE__).'/../var/cache/mustache',
                'loader' => new Mustache_Loader_FilesystemLoader('templates'),
                'partials_loader' => new Mustache_Loader_FilesystemLoader('templates/partials'),
                # 'logger' => $this->logger,
                'charset' => 'UTF-8',
                'strict_callables' => true,
            ));
            $template = $mustache->loadTemplate('contact-form');
            $content = $template->render((object) [
                'name' => FilterGuard::sanitizeString($request->get('name')),
                'subject' => FilterGuard::sanitizeString($request->get('subject')),
                'message' => FilterGuard::sanitizeString($request->get('message')),
            ]);
        } catch (Mustache_Exception $exception) {
            $this->logger->error("Template render error: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Template render error']
                ]);
        }

        try {
            $mailer = $this->getMailer();

            // Sender and recipient settings
            $mailer->setFrom($this->config->get('mail.from.email'), FilterGuard::sanitizeString($this->config->get('mail.from.name')));
            $mailer->addBCC($this->config->get('mail.from.email'), FilterGuard::sanitizeString($this->config->get('mail.from.name')));
            $mailer->addAddress($request->get('email'), FilterGuard::sanitizeString($request->get('name')));

            // Sending plain text email
            $mailer->isHTML(false); // Set email format to plain text
            $mailer->Subject = FilterGuard::sanitizeString($this->config->get('mail.subject'));
            $mailer->Body = $content;

            if (!$mailer->send()) {
                $this->logger->error("Message could not be sent. Mailer Error: {$mailer->ErrorInfo}");

                return $response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setData([
                        'type' => 'mail',
                        'errors' => ['Message could not be sent']
                    ]);
            }
        } catch (PhpMailerException $exception) {
            $this->logger->error("Message could not be sent. Mailer Error: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'mail',
                    'errors' => ['Message could not be sent']
                ]);
        }

        return $response->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    protected function validate(Request $request): void
    {
        $data = $request->request->all();

        $validator = new Validator();

        $ruleSet = [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'subject' => 'required|min:10',
            'message' => 'required|min:10',
        ];

        if ($this->config->get('recaptcha.siteKey')) {
            $ruleSet[$this->config->get('recaptcha.parameterName')] = 'required';
        }

        $validation = $validator->make($data, $ruleSet);

        $validation->validate();

        if ($validation->fails()) {
            throw new ValidationException($validation->errors());
        }
    }

    protected function validateRecaptcha(Request $request): void
    {
        $token = $request->request->get($this->config->get('recaptcha.parameterName'));
        $remoteIp = $request->getClientIp();

        $recaptcha = new ReCaptcha($this->config->get('recaptcha.siteSecret'));
        $response = $recaptcha
            ->setExpectedHostname($this->config->get('recaptcha.siteUrl'))
            ->setExpectedAction($this->config->get('recaptcha.actionName'))
            ->verify($token, $remoteIp);

        if (!$response->isSuccess()) {
            throw new RecaptchaException($response->getErrorCodes());
        }
    }

    protected function getMailer(): PHPMailer
    {
        $phpMailer = new PhpMailer(true);
        $phpMailer->isSMTP();
        $phpMailer->SMTPAuth = true;
        $phpMailer->Host = $this->config->get('mail.host');
        $phpMailer->Username = $this->config->get('mail.username');
        $phpMailer->Password = $this->config->get('mail.password');
        $phpMailer->SMTPSecure = $this->config->get('mail.encryption');
        $phpMailer->Port = $this->config->get('mail.port');
        $phpMailer->CharSet = PHPMailer::CHARSET_UTF8;

        return $phpMailer;
    }

    protected function transformValidationErrors(ValidationException $exception): array
    {
        $messages = [];
        foreach ($exception->getErrors()->toArray() as $key => $errors) {
            $messages[$key] = [];
            foreach (array_keys($errors) as $error) {
                $messages[$key][] = match ($error) {
                    'required' => 'required',
                    'min'      => 'length',
                    'email'    => 'invalid',
                    default    => throw new \RuntimeException("Unknown error: $error"),
                };
            }

            if (count($messages[$key]) >= 1) {
                $messages[$key] = array_shift($messages[$key]);
            }
        }

        return $messages;
    }
}
