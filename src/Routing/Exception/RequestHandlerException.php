<?php

namespace App\Routing\Exception;

use Exception;
use JsonException;
use Throwable;

abstract class RequestHandlerException extends Exception
{
    protected string $type = 'general';

    public function __construct(
        protected readonly array      $errors,
        protected                     $code = 0,
        protected readonly ?Throwable $previous = null
    )
    {
        parent::__construct($this->getFullMessage());
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFullMessage(string $delimiter = PHP_EOL): string
    {
        return implode($delimiter, $this->errors);
    }

    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        return json_encode([
            "type" => $this->getType(),
            "errors" => $this->getErrors()
        ], JSON_THROW_ON_ERROR);
    }
}
