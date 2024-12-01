<?php

namespace App\InternalError\Http;

use App\InternalError\AbstractInternalError;
use App\InternalError\InternalCodes;
use Symfony\Component\HttpFoundation\Response;

class ResponseMappingFailed extends AbstractInternalError
{
    protected int $responseCode = Response::HTTP_OK;
    protected string $errorCode = InternalCodes::RESPONSE_MAPPING_FAILED;
}
