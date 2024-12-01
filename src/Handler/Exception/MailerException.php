<?php

namespace App\Handler\Exception;

use App\Routing\Exception\RequestHandlerException;

class MailerException extends RequestHandlerException
{
    protected string $type = 'mailer';
}
