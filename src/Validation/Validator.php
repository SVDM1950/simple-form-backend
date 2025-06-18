<?php

namespace App\Validation;

use Rakit\Validation\Validator as RakitValidator;

// Wrapper-Klasse um die __invoke()-Funktion von Rakit\Validation\Validator vor Pimple\Container zu vertecken
class Validator
{
    protected RakitValidator $validator;

    public function __construct()
    {
        $this->validator = new RakitValidator();
    }

    public function __call(string $name, array $arguments) : mixed
    {
        if (!method_exists(RakitValidator::class, $name)) {
            throw new \BadMethodCallException("Method {$name} does not exist in " . RakitValidator::class);
        }

        return $this->validator->$name(...$arguments);
    }

    public function invoke(...$arguments) : mixed {
        return $this->validator->__invoke(...$arguments);
    }
}
