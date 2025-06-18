<?php

namespace App\Validation;

use App\Routing\Interface\ContainerAware;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use App\Validation\Rule\AtLeastOneTicketRule;
use App\Validation\Rule\SupervisorMinimumRule;
use Pimple\Container;
use Rakit\Validation\RuleQuashException;

class ValidatorFactory implements ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected Container $container)
    {
    }

    /**
     * @throws RuleQuashException
     */
    public function __invoke(): Validator
    {
        $validator = new Validator();

        // Registrieren Sie die benutzerdefinierten Regeln
        $validator->addValidator('at_least_one_ticket', new AtLeastOneTicketRule());
        // $validator->addValidator('supervisor_minimum', new SupervisorMinimumRule());

        return $validator;
    }
}
