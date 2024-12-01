<?php

namespace App\Exception;

use Exception;

class StopException extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}
