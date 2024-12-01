<?php

namespace App\InternalError;

class InternalCodes
{
    //skeleton
    public const PREFIX = 'SFB';

    // unhandled error
    public const UNEXPECTED = self::PREFIX . '000';

    // general invalid requests 001 - 049
    public const EMPTY_REQUEST_BODY  = self::PREFIX . '001';
    public const CAN_NOT_PARSE_JSON  = self::PREFIX . '002';
    public const INVALID_REQUEST     = self::PREFIX . '003';
    public const NOT_FOUND           = self::PREFIX . '004';

    // infrastructure errors 050 - 099

    // application errors 100 - 399

    public const RESPONSE_MAPPING_FAILED = self::PREFIX . '400';
}
