<?php

namespace App\InternalError\Http;

use App\InternalError\AbstractInternalError;
use App\InternalError\InternalCodes;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

class NotFoundError extends AbstractInternalError
{
    protected int $responseCode = Response::HTTP_NOT_FOUND;
    protected string $clientMessage = 'Resource not found';
    protected string $errorCode = InternalCodes::NOT_FOUND;
    protected string $logPriority = LogLevel::NOTICE;
}
