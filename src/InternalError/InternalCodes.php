<?php

namespace App\InternalError;

class InternalCodes
{
    //skeleton
    public const string PREFIX = 'SFB';

    // unhandled error
    public const string UNEXPECTED = self::PREFIX . '000';

    // general invalid requests 001 - 049
    public const string EMPTY_REQUEST_BODY = self::PREFIX . '001';
    public const string CAN_NOT_PARSE_JSON = self::PREFIX . '002';
    public const string INVALID_REQUEST = self::PREFIX . '003';
    public const string NOT_FOUND       = self::PREFIX . '004';

    // infrastructure errors 050 - 099

    // application errors 100 - 399

    public const string RESPONSE_MAPPING_FAILED = self::PREFIX . '400';
}
