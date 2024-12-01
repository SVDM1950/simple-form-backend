<?php

namespace App\InternalError\Http;

use App\InternalError\AbstractInternalError;
use App\InternalError\InternalCodes;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

class CanNotParseJson extends AbstractInternalError
{
    protected int $responseCode = Response::HTTP_BAD_REQUEST;
    protected string $clientMessage = 'Can not json decode request body';
    protected string $errorCode = InternalCodes::CAN_NOT_PARSE_JSON;
    protected string $logPriority = LogLevel::NOTICE;

    /**
     * CanNotParseJson constructor.
     */
    public function __construct(string $jsonError)
    {
        parent::__construct($this->message, $this->code, new InvalidArgumentException($jsonError));
    }
}
