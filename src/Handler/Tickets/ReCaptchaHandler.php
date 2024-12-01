<?php

namespace App\Handler\Tickets;

use App\Config;
use App\Handler\Exception\RecaptchaException;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use Rakit\Validation\Validator;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReCaptchaHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @throws ValidationException
     * @throws RecaptchaException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        if ($this->config()->get('recaptcha.enabled') && $this->config()->get('recaptcha.siteKey')) {
            $this->validate($request);
            $this->verify($request);
//            try {
//                $this->validateRecaptcha($request);
//            } catch (RecaptchaException $exception) {
//                $this->logger()->error("Invalid reCAPTCHA data: {$exception->getMessage()}");
//
//                return $response
//                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
//                    ->setData([
//                        'type' => 'recaptcha',
//                        'errors' => ['Invalid reCAPTCHA data']
//                    ]);
//            } catch (Exception $exception) {
//                $this->logger()->error("Invalid reCAPTCHA data: {$exception->getMessage()}");
//
//                return $response
//                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
//                    ->setData([
//                        'type' => 'recaptcha',
//                        'errors' => ['Invalid reCAPTCHA data']
//                    ]);
//            }
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
        return $this->config()->get('recaptcha.parameterName');
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }

    protected function reCaptcha(): ReCaptcha
    {
        return $this->container['recaptcha.tickets'];
    }
}
