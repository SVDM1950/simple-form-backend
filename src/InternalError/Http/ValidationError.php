<?php

namespace App\InternalError\Http;

use App\InternalError\AbstractInternalError;
use App\InternalError\InternalCodes;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception for invalid request
 */
class ValidationError extends AbstractInternalError
{
    protected int $responseCode = Response::HTTP_BAD_REQUEST;
    protected string $clientMessage = 'Invalid request';
    protected string $errorCode = InternalCodes::INVALID_REQUEST;
    protected string $logPriority = LogLevel::INFO;

    protected array $validationErrors;

    public function __construct(array $errors)
    {
        parent::__construct('Invalid request');

        $this->validationErrors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
