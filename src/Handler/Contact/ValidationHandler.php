<?php

namespace App\Handler\Contact;

use App\Config;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use Rakit\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidationHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @throws ValidationException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        $data = $request->request->all();

        $validator = $this->validator();

        $ruleSet = [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'subject' => 'required|min:10',
            'message' => 'required|min:10',
        ];

        $validation = $validator->make($data, $ruleSet);
        $validation->validate();

        if ($validation->fails()) {
            throw new ValidationException(
                $validation->errors()->toArray(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $handler->handle($request, $response);
    }

    protected function config(): Config
    {
        return $this->container[Config::class];
    }

    protected function validator(): Validator
    {
        return $this->container[Validator::class];
    }
}
