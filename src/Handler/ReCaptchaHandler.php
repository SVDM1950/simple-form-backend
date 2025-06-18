<?php

namespace App\Handler;

use App\Config;
use App\Handler\Exception\RecaptchaException;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use App\Validation\Validator;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class ReCaptchaHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected string $section)
    {
    }

    /**
     * @throws ValidationException
     * @throws RecaptchaException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        if ($this->config()->get("recaptcha.{$this->section}.enabled") && $this->config()->get("recaptcha.{$this->section}.siteKey")) {
            $this->validate($request);
            $this->verify($request);
        }

        return $handler->handle($request, $response);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(Request $request): void
    {
        $data = $request->request->all();

        $validator = new Validator();
        $ruleSet[$this->getParameterName()] = 'required';
        $validation = $validator->make($data, $ruleSet);
        $validation->validate();

        if ($validation->fails()) {
            throw new ValidationException($validation->errors()->toArray(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function verify(Request $request): void
    {
        $token = $request->request->get($this->getParameterName());
        $remoteIp = $request->getClientIp();

        $response = $this->reCaptcha()->verify($token, $remoteIp);

        if (!$response->isSuccess()) {
            throw new RecaptchaException($response->getErrorCodes());
        }
    }

    protected function getParameterName(): string
    {
        return $this->config()->get("recaptcha.{$this->section}.parameterName");
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }

    protected function reCaptcha(): ReCaptcha
    {
        return $this->container["recaptcha.{$this->section}"];
    }

    public function logger(): LoggerInterface
    {
        return $this->container[$this->config()->get('services.logger.class')];
    }
}
