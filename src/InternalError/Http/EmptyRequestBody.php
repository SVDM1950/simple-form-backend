<?php

namespace App\InternalError\Http;

use App\InternalError\InternalCodes;
use App\InternalError\AbstractInternalError;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception if the request body is required but empty
 *
 * @copyright Copyright (c) 2017 Invia Flights Germany GmbH
 * @author    Fluege-Dev <fluege-dev@invia.de>
 */
class EmptyRequestBody extends AbstractInternalError
{
    protected int $responseCode = Response::HTTP_BAD_REQUEST;
    protected string $clientMessage = 'Request body can not be empty';
    protected string $errorCode = InternalCodes::EMPTY_REQUEST_BODY;
    protected string $logPriority = LogLevel::NOTICE;
}
