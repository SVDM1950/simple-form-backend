<?php

namespace App\Handler\Exception;

use App\Routing\Exception\RequestHandlerException;

class ValidationException extends RequestHandlerException
{
    protected string $type = 'validation';

    public function getFullMessage(string $delimiter = PHP_EOL): string
    {
        $results = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $results[] = $this->formatMessage($message, ':message');
            }
        }

        return implode($delimiter, $results);
    }

    protected function formatMessage(string $message, string $format): string
    {
        return str_replace(':message', $message, $format);
    }

}
