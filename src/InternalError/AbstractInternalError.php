<?php

namespace App\InternalError;

use Exception;
use Psr\Log\LogLevel;
use Throwable;

class AbstractInternalError extends Exception
{
    protected int $responseCode = 500;

    protected string $clientMessage = 'not defined yet';

    protected string $errorCode = 'X999';

    protected string $requestField = '_';

    protected string $logPriority = LogLevel::ERROR;

    /**
     * AbstractInternalError constructor.
     */
    public function __construct(string $logMessage = null, int $code = 0, Throwable $previous = null)
    {
        $logMessage          = $logMessage ?? $this->clientMessage ?? 'unknown error';
        $this->clientMessage = $this->clientMessage ?? $logMessage;

        parent::__construct($logMessage, $code, $previous);
    }

    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    public function getClientMessage(): string
    {
        return $this->clientMessage;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRequestField(): string
    {
        return $this->requestField;
    }

    public function getLogPriority(): string
    {
        return $this->logPriority;
    }
}
