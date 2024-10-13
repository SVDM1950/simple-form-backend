<?php

namespace App;

use InvalidArgumentException;
use Rakit\Validation\ErrorBag;

class RecaptchaException extends InvalidArgumentException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct($this->getFullMessage());
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFullMessage(): string
    {
        return implode(", ", $this->errors);
    }

    public function __toString(): string
    {
        return $this->getMessage();
    }
}
