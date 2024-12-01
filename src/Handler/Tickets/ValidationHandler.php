<?php

namespace App\Handler\Tickets;

use App\Config;
use App\Handler\Exception\ValidationException;
use App\Routing\Interface\ContainerAware;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use Rakit\Validation\RuleNotFoundException;
use Rakit\Validation\Rules\In;
use Rakit\Validation\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidationHandler implements RequestHandler, ContainerAware
{
    use ContainerAwareTrait;

    /**
     * @throws ValidationException
     * @throws RuleNotFoundException
     */
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        $data = $request->request->all();

        $validator = $this->validator();

        $eventIds = array_map(
            callback: fn($key) => (string) $key,
            array: array_keys($this->config()->get('events'))
        );

        /** @var In $eventValidator */
        $eventValidator = $validator('in', $eventIds);
        $eventValidator->strict();

        $ticketIds = array_map(
            callback: fn($key) => (string) $key,
            array: array_keys($this->config()->get('tickets'))
        );

        $ruleSet = [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'event' => ['required', $eventValidator],
            'message' => 'min:10',
            'tickets' => 'required|array|at_least_one_ticket'
        ];

        foreach($ticketIds as $ticketId) {
            $ruleSet["tickets.{$ticketId}"] = 'required|integer|min:0';
        }

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
