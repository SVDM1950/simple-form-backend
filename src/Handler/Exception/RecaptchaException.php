<?php

namespace App\Handler\Exception;

use App\Routing\Exception\RequestHandlerException;

class RecaptchaException extends RequestHandlerException
{
    protected string $type = 'recaptcha';
}
