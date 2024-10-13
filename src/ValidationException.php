<?php

namespace App;

use InvalidArgumentException;
use Rakit\Validation\ErrorBag;

class ValidationException extends InvalidArgumentException
{
    public function __construct(private readonly ErrorBag $errors)
    {
        parent::__construct($this->getFullMessage());
    }

    public function getErrors(): ErrorBag
    {
        return $this->errors;
    }

    public function getFullMessage(): string
    {
        return implode(PHP_EOL, $this->errors->all());
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}
