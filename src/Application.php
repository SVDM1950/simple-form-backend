<?php

namespace App;

use App\Handler\Exception\RecaptchaException;
use App\Handler\Exception\ValidationException;
use App\Routing\Router;
use Exception;
use FilterGuard\FilterGuard;
use JsonException;
use Mustache_Engine;
use Mustache_Exception;
use Mustache_Loader_FilesystemLoader;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Rakit\Validation\Validator;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function file_exists;
use function is_dir;
use function is_readable;
use function is_string;

class Application
{
    protected Container $container;

    public function __construct($configPath = null)
    {
        $this->container = new Container();

        $this->container[Config::class] = new Config();

        if (is_string($configPath) && file_exists($configPath) && is_readable($configPath) && is_dir($configPath)) {
            $this->config()->loadConfigurationDirectory($configPath);
        }

        if ($this->config()->has('services')) {
            $this->registerServices();
        }

        if ($this->config()->has('routes')) {
            $this->registerRoutes();
        }
    }

    /**
     * Register services in the container
     */
    protected function registerServices(): void
    {
        foreach ($this->config()->get('services') as $serviceId => $service) {
            $className = $service['class'] ?? $serviceId;
            $serviceId = $service['id'] ?? $className;
            $arguments = $this->replaceConfigParams($service['arguments'] ?? []);

            if (array_key_exists('factory', $service)) {
                $factoryClass = $service['factory'];
                $this->container[$serviceId] = (new $factoryClass($this->container))(...$arguments);
                continue;
            }

            $this->container[$serviceId] = new $className(...$arguments);
        }
    }

    protected function registerRoutes(): void
    {
        foreach ($this->config()->get('routes') as $route) {
            $this->router()->addRoute(
                $route['method'] ?? 'GET',
                $route['path'],
                $this->replaceConfigParams($route['handlers'])
            );
        }
    }

    /**
     * Replace config parameters in the given array
     */
    protected function replaceConfigParams(array $params): array
    {
        return array_map(function ($value) {
            if (is_string($value) && \preg_match('/^%(.+)%$/', $value, $matches)) {
                return $this->config()->get($matches[1]);
            } elseif (is_string($value) && $value === '@container') {
                return $this->container;
            } elseif (is_string($value) && \preg_match('/^@(.+)$/', $value, $matches)) {
                return $this->container[$this->config()->get("{${$matches[1]}}.class")];
            }

            return $value;
        }, $params);
    }

    /**
     * Dispatch the request, execute the middlewares, send and return the response
     */
    public function run(Request $request = null): Response
    {
        $request = $request ?? Request::createFromGlobals();

        $response = $this->router()->handleRequest($request);

        if (ob_get_length()) {
            @ob_end_clean(); // remove every output, so we have a clean response
        }

        $response->send();

        return $response;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function config(): Config
    {
        return $this->container[Config::class];
    }

    public function logger(): LoggerInterface
    {
        return $this->container[$this->config()->get('services.logger.class')];
    }

    public function router(): Router
    {
        return $this->container[$this->config()->get('services.router.class')];
    }


    protected function handle_old(Request $request): Response
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
            $this->logger()->error("HTTP method '{$request->getMethod()}' not allowed");

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
            $this->logger()->error("Invalid JSON data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_BAD_REQUEST)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Invalid JSON data']
                ]);
        } catch (Exception $exception) {
            $this->logger()->error("Invalid JSON data: {$exception->getMessage()}");

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
            $this->logger()->error("Invalid data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setData([
                    'type' => 'validation',
                    'errors' => $this->transformValidationErrors($exception)
                ]);
        } catch (Exception $exception) {
            $this->logger()->error("Invalid data: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'general',
                    'errors' => ['Invalid data']
                ]);
        }

        if ($this->config()->get('recaptcha.siteKey')) {
            try {
                $this->validateRecaptcha($request);
            } catch (RecaptchaException $exception) {
                $this->logger()->error("Invalid reCAPTCHA data: {$exception->getMessage()}");

                return $response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setData([
                        'type' => 'recaptcha',
                        'errors' => ['Invalid reCAPTCHA data']
                    ]);
            } catch (Exception $exception) {
                $this->logger()->error("Invalid reCAPTCHA data: {$exception->getMessage()}");

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
                # 'logger' => $this->logger(),
                'charset' => 'UTF-8',
                'strict_callables' => true,
            ));
            $template = $mustache->loadTemplate('contact-form');
            $content = $template->render((object)[
                'name' => FilterGuard::sanitizeString($request->get('name')),
                'subject' => FilterGuard::sanitizeString($request->get('subject')),
                'message' => FilterGuard::sanitizeString($request->get('message')),
            ]);
        } catch (Mustache_Exception $exception) {
            $this->logger()->error("Template render error: {$exception->getMessage()}");

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
            $mailer->setFrom($this->config()->get('mail.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.from.name')));
            $mailer->addBCC($this->config()->get('mail.from.email'), FilterGuard::sanitizeString($this->config()->get('mail.from.name')));
            $mailer->addAddress($request->get('email'), FilterGuard::sanitizeString($request->get('name')));

            // Sending plain text email
            $mailer->isHTML(false); // Set email format to plain text
            $mailer->Subject = FilterGuard::sanitizeString($this->config()->get('mail.subject'));
            $mailer->Body = $content;

            if (!$mailer->send()) {
                $this->logger()->error("Message could not be sent. Mailer Error: {$mailer->ErrorInfo}");

                return $response
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setData([
                        'type' => 'mail',
                        'errors' => ['Message could not be sent']
                    ]);
            }
        } catch (PHPMailerException $exception) {
            $this->logger()->error("Message could not be sent. Mailer Error: {$exception->getMessage()}");

            return $response
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
                ->setData([
                    'type' => 'mail',
                    'errors' => ['Message could not be sent']
                ]);
        }

        return $response->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws ValidationException
     */
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

        if ($this->config()->get('recaptcha.siteKey')) {
            $ruleSet[$this->config()->get('recaptcha.parameterName')] = 'required';
        }

        $validation = $validator->make($data, $ruleSet);
        $validation->validate();

        if ($validation->fails()) {
            throw new ValidationException($validation->errors()->toArray(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function validateRecaptcha(Request $request): void
    {
        $token = $request->request->get($this->config()->get('recaptcha.parameterName'));
        $remoteIp = $request->getClientIp();

        $recaptcha = new ReCaptcha($this->config()->get('recaptcha.siteSecret'));
        $response = $recaptcha
            ->setExpectedHostname($this->config()->get('recaptcha.siteUrl'))
            ->setExpectedAction($this->config()->get('recaptcha.actionName'))
            ->verify($token, $remoteIp);

        if (!$response->isSuccess()) {
            throw new RecaptchaException($response->getErrorCodes());
        }
    }

    protected function getMailer(): PHPMailer
    {
        $phpMailer = new PHPMailer(true);
        $phpMailer->isSMTP();
        $phpMailer->SMTPAuth = true;
        $phpMailer->Host = $this->config()->get('mail.host');
        $phpMailer->Username = $this->config()->get('mail.username');
        $phpMailer->Password = $this->config()->get('mail.password');
        $phpMailer->SMTPSecure = $this->config()->get('mail.encryption');
        $phpMailer->Port = $this->config()->get('mail.port');
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
